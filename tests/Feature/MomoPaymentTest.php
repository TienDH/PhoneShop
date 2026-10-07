<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\MomoService;
use Illuminate\Support\Facades\Http;
use Tests\StoreTestCase;

class MomoPaymentTest extends StoreTestCase
{
    private function fakeGateway()
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(function ($request) {
            return Http::response([
                'resultCode' => 0, 'message' => 'Successful',
                'orderId' => $request['orderId'], 'requestId' => $request['requestId'],
                'payUrl' => 'https://test-payment.momo.vn/v2/gateway/pay?t=fixture',
            ]);
        });
    }

    private function payload(PaymentTransaction $transaction, array $overrides = []): array
    {
        $request = $transaction->fresh()->request_payload;
        $payload = array_merge([
            'amount' => (int) $transaction->amount, 'extraData' => $request['extraData'],
            'message' => 'Successful', 'orderId' => $transaction->gateway_order_id,
            'orderInfo' => $request['orderInfo'], 'orderType' => 'momo_wallet',
            'partnerCode' => config('services.momo.partner_code'), 'payType' => 'qr',
            'requestId' => $request['requestId'], 'responseTime' => 1790000000000,
            'resultCode' => 0, 'transId' => 123456789,
        ], $overrides);
        $parts = ['accessKey=' . config('services.momo.access_key')];
        foreach (['amount', 'extraData', 'message', 'orderId', 'orderInfo', 'orderType', 'partnerCode', 'payType', 'requestId', 'responseTime', 'resultCode', 'transId'] as $field) {
            $parts[] = $field . '=' . $payload[$field];
        }
        $payload['signature'] = hash_hmac('sha256', implode('&', $parts), config('services.momo.secret_key'));
        return $payload;
    }

    public function test_momo_checkout_creates_an_attempt_and_redirects_to_the_gateway()
    {
        $this->fakeGateway();
        $this->checkout(['payment_method' => 'momo'])->assertRedirect('https://test-payment.momo.vn/v2/gateway/pay?t=fixture');
        $transaction = PaymentTransaction::firstOrFail();
        $this->assertSame('initiated', $transaction->status);
        $this->assertEquals(230000, $transaction->amount);
        $this->assertSame('captureWallet', $transaction->request_payload['requestType']);
        $this->assertSame('TDH Phone', $transaction->request_payload['partnerName']);
        $this->assertSame('pending', $transaction->order->payment_status);
        $this->assertSame(3, $this->variant->fresh()->stock);
        Http::assertSentCount(1);
    }

    public function test_card_and_atm_methods_use_the_correct_momo_request_types()
    {
        $this->fakeGateway();
        foreach (['momo_atm' => 'payWithATM', 'momo_card' => 'payWithCC'] as $method => $type) {
            $order = $this->order(['payment_method' => $method]);
            $this->post(route('orders.momo.pay', $order))->assertRedirect();
            $this->assertSame($type, $order->transactions()->first()->request_payload['requestType']);
        }
    }

    public function test_signed_ipn_and_callback_settle_once_and_never_regress()
    {
        $this->fakeGateway();
        $this->checkout(['payment_method' => 'momo']);
        $transaction = PaymentTransaction::firstOrFail();
        $payload = $this->payload($transaction);
        $this->postJson(route('payment.momo.ipn'), $payload)->assertNoContent();
        $this->postJson(route('payment.momo.ipn'), $payload)->assertNoContent();
        $this->get(route('payment.momo.callback', $payload))->assertRedirect(route('orders.show', $transaction->order_id));
        $paidAt = $transaction->fresh()->paid_at->toDateTimeString();
        $this->postJson(route('payment.momo.ipn'), $this->payload($transaction, ['resultCode' => 1006, 'message' => 'Cancelled']))->assertNoContent();
        $this->assertSame('paid', $transaction->fresh()->status);
        $this->assertSame($paidAt, $transaction->fresh()->paid_at->toDateTimeString());
        $this->assertSame('paid', $transaction->order->fresh()->payment_status);
        $this->assertSame(2, $transaction->order->histories()->count());
        $this->assertSame(3, $this->variant->fresh()->stock);
        $this->postJson(route('orders.momo.pay', $transaction->order))->assertUnprocessable();
        $this->assertSame(1, PaymentTransaction::count());
    }

    public function test_invalid_signature_amount_request_and_partner_do_not_settle()
    {
        $this->fakeGateway();
        $this->checkout(['payment_method' => 'momo']);
        $transaction = PaymentTransaction::firstOrFail();
        $payload = $this->payload($transaction);
        $payload['signature'] = str_repeat('0', 64);
        $this->postJson(route('payment.momo.ipn'), $payload)->assertStatus(400);
        foreach ([['amount' => 1], ['requestId' => 'FORGED'], ['partnerCode' => 'OTHER'], ['extraData' => 'OTHER']] as $mismatch) {
            $this->postJson(route('payment.momo.ipn'), $this->payload($transaction, $mismatch))->assertStatus(400);
        }
        $this->assertSame('initiated', $transaction->fresh()->status);
        $this->assertSame('pending', $transaction->order->fresh()->payment_status);
    }

    public function test_failed_payment_retry_is_a_new_attempt_for_the_same_order()
    {
        $this->fakeGateway();
        $this->checkout(['payment_method' => 'momo']);
        $first = PaymentTransaction::firstOrFail();
        $this->postJson(route('payment.momo.ipn'), $this->payload($first, ['resultCode' => 1006, 'message' => 'Cancelled']))->assertNoContent();
        $this->assertSame('failed', $first->fresh()->status);
        $this->assertSame('failed', $first->order->fresh()->payment_status);
        $this->post(route('orders.momo.pay', $first->order))->assertRedirect();
        $second = PaymentTransaction::latest('id')->first();
        $this->assertNotSame($first->gateway_order_id, $second->gateway_order_id);
        $this->assertSame($first->order_id, $second->order_id);
        $this->assertSame(1, Order::count());
        $this->assertSame(3, $this->variant->fresh()->stock);
        $this->postJson(route('payment.momo.ipn'), $this->payload($second))->assertNoContent();
        $this->assertSame('paid', $second->fresh()->status);
    }

    public function test_an_active_attempt_is_reused_and_bad_gateway_urls_are_rejected()
    {
        $this->fakeGateway();
        $order = $this->order(['payment_method' => 'momo']);
        $this->post(route('orders.momo.pay', $order))->assertRedirect();
        $this->post(route('orders.momo.pay', $order))->assertRedirect();
        Http::assertSentCount(1);
        $this->assertSame(1, $order->transactions()->count());

        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(function ($request) {
            return Http::response(['resultCode' => 0, 'orderId' => $request['orderId'], 'requestId' => $request['requestId'], 'payUrl' => 'https://attacker.example/pay']);
        });
        $other = $this->order(['payment_method' => 'momo']);
        $this->postJson(route('orders.momo.pay', $other))->assertUnprocessable();
        $this->assertSame('failed', $other->transactions()->first()->status);
    }

    public function test_late_payment_does_not_reopen_a_cancelled_order()
    {
        $this->fakeGateway();
        $this->checkout(['payment_method' => 'momo']);
        $transaction = PaymentTransaction::firstOrFail();
        $this->post(route('orders.cancel', $transaction->order))->assertRedirect();
        $this->postJson(route('payment.momo.ipn'), $this->payload($transaction))->assertNoContent();
        $this->assertSame('paid', $transaction->fresh()->status);
        $this->assertSame('cancelled', $transaction->order->fresh()->status);
        $this->assertSame('refund_pending', $transaction->order->fresh()->payment_status);
        $this->assertSame(5, $this->variant->fresh()->stock);
    }

    public function test_failed_create_keeps_order_and_stock_for_retry()
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['*' => Http::response(['resultCode' => 11, 'message' => 'Invalid credentials'], 400)]);
        $this->checkout(['payment_method' => 'momo'])->assertRedirect();
        $this->assertSame(1, Order::count());
        $this->assertSame('failed', PaymentTransaction::first()->status);
        $this->assertSame(3, $this->variant->fresh()->stock);
        $this->assertSame([], session('cart'));
    }
}
