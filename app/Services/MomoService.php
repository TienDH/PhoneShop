<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MomoService
{
    public function isConfigured(): bool
    {
        return config('services.momo.partner_code') && config('services.momo.access_key') && config('services.momo.secret_key');
    }

    public function paymentUrl(Order $order): string
    {
        if (!$this->isConfigured()) {
            throw ValidationException::withMessages(['payment' => 'MoMo chưa sẵn sàng. Vui lòng liên hệ cửa hàng.']);
        }
        $transaction = DB::transaction(function () use ($order) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            if (!$order->uses_momo || in_array($order->payment_status, ['paid', 'refund_pending']) || $order->status === 'cancelled') {
                throw ValidationException::withMessages(['payment' => 'Đơn hàng này không thể thanh toán lại.']);
            }
            if ((int) $order->total < 1000 || (int) $order->total > 50000000) {
                throw ValidationException::withMessages(['payment' => 'MoMo hỗ trợ giao dịch từ 1.000 đến 50.000.000 đồng.']);
            }
            $active = $order->transactions()->where('gateway', 'momo')->whereIn('status', ['pending', 'initiated'])->latest('id')->first();
            if ($active) {
                if ($active->status === 'pending') {
                    throw ValidationException::withMessages(['payment' => 'Yêu cầu thanh toán đang được xử lý. Vui lòng thử lại sau.']);
                }
                return $active;
            }
            return $order->transactions()->create([
                'gateway' => 'momo', 'amount' => $order->total, 'status' => 'pending',
                'gateway_order_id' => 'TDH-' . $order->id . '-' . Str::uuid(),
            ]);
        });

        if ($transaction->status === 'initiated') {
            return $transaction->response_payload['payUrl'];
        }
        try {
            $result = $this->createPayment($order, $transaction);
        } catch (\Throwable $exception) {
            $transaction->newQuery()->whereKey($transaction->id)->where('status', 'pending')
                ->update(['status' => 'failed', 'message' => 'Không thể kết nối MoMo.']);
            report($exception);
            throw ValidationException::withMessages(['payment' => 'Không thể kết nối MoMo. Bạn có thể thanh toán lại từ đơn hàng này.']);
        }
        if (($result['resultCode'] ?? -1) != 0 || empty($result['payUrl'])) {
            throw ValidationException::withMessages(['payment' => 'MoMo chưa tạo được thanh toán. Vui lòng thử lại hoặc liên hệ cửa hàng.']);
        }
        return $result['payUrl'];
    }

    public function createPayment(Order $order, PaymentTransaction $transaction): array
    {
        $types = ['momo_atm' => 'payWithATM', 'momo_card' => 'payWithCC'];
        $data = [
            'partnerCode' => config('services.momo.partner_code'),
            'partnerName' => 'TDH Phone', 'storeName' => 'TDH Phone',
            'requestId' => (string) Str::uuid(),
            'amount' => (int) $transaction->amount,
            'orderId' => $transaction->gateway_order_id,
            'orderInfo' => 'Thanh toan don hang ' . $order->order_code,
            'redirectUrl' => config('services.momo.redirect_url') ?: route('payment.momo.callback'),
            'ipnUrl' => config('services.momo.ipn_url') ?: route('payment.momo.ipn'),
            'extraData' => base64_encode(json_encode(['order_id' => $order->id])),
            'requestType' => $types[$order->payment_method] ?? config('services.momo.request_type', 'captureWallet'),
            'autoCapture' => true, 'lang' => 'vi',
        ];
        $data['signature'] = $this->sign($data, [
            'amount', 'extraData', 'ipnUrl', 'orderId', 'orderInfo',
            'partnerCode', 'redirectUrl', 'requestId', 'requestType',
        ]);
        $transaction->update(['request_payload' => $data]);
        $response = Http::withOptions(['verify' => (bool) config('services.momo.verify_ssl', true), 'connect_timeout' => 10])
            ->timeout(35)->post(config('services.momo.endpoint'), $data);
        $result = $response->json() ?: [];
        $validUrl = isset($result['payUrl']) && $this->isMomoUrl($result['payUrl']);
        $valid = $response->successful() && ($result['resultCode'] ?? -1) == 0 && $validUrl
            && ($result['orderId'] ?? null) === $data['orderId']
            && ($result['requestId'] ?? null) === $data['requestId'];
        // An IPN may arrive before the create request returns; never overwrite a settled transaction.
        PaymentTransaction::whereKey($transaction->id)->where('status', 'pending')->update([
            'response_payload' => json_encode($result),
            'result_code' => isset($result['resultCode']) ? (int) $result['resultCode'] : null,
            'message' => mb_substr((string) ($result['message'] ?? ''), 0, 1000),
            'status' => $valid ? 'initiated' : 'failed',
            'updated_at' => now(),
        ]);
        return $valid ? $result : ['resultCode' => $result['resultCode'] ?? -1];
    }

    private function isMomoUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        return parse_url($url, PHP_URL_SCHEME) === 'https'
            && is_string($host) && ($host === 'momo.vn' || Str::endsWith($host, '.momo.vn'));
    }

    private function sign(array $payload, array $fields): string
    {
        $parts = ['accessKey=' . config('services.momo.access_key')];
        foreach ($fields as $field) {
            $parts[] = $field . '=' . ($payload[$field] ?? '');
        }
        return hash_hmac('sha256', implode('&', $parts), config('services.momo.secret_key', ''));
    }

    public function isValidResponse(array $payload): bool
    {
        $fields = ['amount', 'extraData', 'message', 'orderId', 'orderInfo', 'orderType', 'partnerCode', 'payType', 'requestId', 'responseTime', 'resultCode', 'transId'];
        if (!$this->isConfigured() || !isset($payload['signature']) || !is_string($payload['signature'])) {
            return false;
        }
        foreach ($fields as $field) {
            if (!array_key_exists($field, $payload) || !is_scalar($payload[$field])) {
                return false;
            }
        }
        return $payload['partnerCode'] === config('services.momo.partner_code')
            && hash_equals($this->sign($payload, $fields), $payload['signature']);
    }

    public function handleNotification(array $payload): PaymentTransaction
    {
        abort_unless($this->isValidResponse($payload), 400, 'Invalid MoMo signature.');
        $reference = PaymentTransaction::where('gateway', 'momo')->where('gateway_order_id', $payload['orderId'])->firstOrFail();

        return DB::transaction(function () use ($reference, $payload) {
            // All payment and order updates acquire the order lock first.
            $order = Order::lockForUpdate()->findOrFail($reference->order_id);
            $transaction = PaymentTransaction::lockForUpdate()->findOrFail($reference->id);
            $request = $transaction->request_payload ?: [];
            abort_unless(
                preg_match('/^\d+$/', (string) $payload['amount'])
                && (string) (int) $transaction->amount === (string) $payload['amount']
                && ($request['requestId'] ?? null) === $payload['requestId']
                && ($request['extraData'] ?? null) === $payload['extraData']
                && ($request['orderInfo'] ?? null) === $payload['orderInfo'],
                400, 'MoMo transaction does not match.'
            );
            if ($transaction->status === 'paid') {
                return $transaction;
            }

            $paid = (string) $payload['resultCode'] === '0';
            $transaction->update([
                'status' => $paid ? 'paid' : 'failed',
                'transaction_id' => (string) $payload['transId'],
                'result_code' => (int) $payload['resultCode'],
                'message' => mb_substr((string) $payload['message'], 0, 1000),
                'response_payload' => $payload, 'paid_at' => $paid ? now() : null,
            ]);
            if ($paid) {
                $alreadyPaid = $order->payment_status === 'paid';
                $order->update(['payment_status' => $order->status === 'cancelled' ? 'refund_pending' : 'paid']);
                if (!$alreadyPaid) {
                    $order->histories()->create(['status' => $order->status, 'note' => 'MoMo đã xác nhận thanh toán.']);
                }
            } elseif (!in_array($order->payment_status, ['paid', 'refund_pending']) && $order->status !== 'cancelled') {
                $latest = $order->transactions()->where('gateway', 'momo')->latest('id')->first();
                if ($latest->id === $transaction->id) {
                    $order->update(['payment_status' => 'failed']);
                }
            }
            return $transaction;
        });
    }
}
