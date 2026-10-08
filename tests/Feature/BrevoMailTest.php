<?php

namespace Tests\Feature;

use App\Mail\Transport\BrevoTransport;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Request;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\StoreTestCase;

class BrevoMailTest extends StoreTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.url' => 'https://phoneshop-bwkm.onrender.com', 'app.trusted_proxies' => '*',
            'mail.default' => 'brevo', 'services.brevo.key' => 'TEST_BREVO_KEY_DO_NOT_PRINT',
            'mail.from.address' => 'store@tdhphone.vn', 'mail.from.name' => 'TDH Phone',
        ]);
        Notification::swap(new ChannelManager($this->app));
        app('mail.manager')->purge('brevo');
        Http::swap(new Factory());
        Http::fake(function ($request, $options) {
            $this->assertSame('https://api.brevo.com/v3/smtp/email', $request->url());
            $this->assertSame('POST', $request->method());
            $this->assertFalse($options['allow_redirects']);
            $this->assertSame(5, $options['connect_timeout']);
            $this->assertSame(15, $options['timeout']);
            $this->assertTrue($options['verify'] ?? true);
            return Http::response(['messageId' => '<test-message@brevo.test>'], 201);
        });
    }

    protected function tearDown(): void
    {
        Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_PROTO);
        parent::tearDown();
    }

    public function test_registered_mailer_sends_plain_text_without_smtp()
    {
        $this->assertInstanceOf(BrevoTransport::class, $this->transport());
        Mail::raw('Hello from TDH Phone', function ($message) {
            $message->to('customer@example.net')->subject('Brevo test');
        });
        Http::assertSent(function ($request) {
            return $request->hasHeader('api-key', 'TEST_BREVO_KEY_DO_NOT_PRINT')
                && $request->hasHeader('Accept', 'application/json')
                && $request->hasHeader('Content-Type', 'application/json')
                && $request['sender'] === ['email' => 'store@tdhphone.vn', 'name' => 'TDH Phone']
                && $request['to'] === [['email' => 'customer@example.net']]
                && $request['subject'] === 'Brevo test'
                && $request['textContent'] === 'Hello from TDH Phone'
                && !isset($request['htmlContent']);
        });
        Http::assertSentCount(1);
    }

    public function test_registration_sends_verification_email_through_brevo()
    {
        $this->post('/logout');
        $this->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->post('http://phoneshop-bwkm.onrender.com/register', [
                'name' => 'New Customer', 'email' => 'new-customer@example.net',
                'password' => 'RegistrationTest2026!', 'password_confirmation' => 'RegistrationTest2026!',
            ])->assertRedirect('/email/verify');

        $user = User::where('email', 'new-customer@example.net')->firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());
        Http::assertSent(function ($request) use ($user) {
            return $request['to'][0]['email'] === $user->email
                && str_contains($request['htmlContent'], '/email/verify/' . $user->id . '/')
                && str_contains($request['textContent'], '/email/verify/' . $user->id . '/');
        });
        Http::assertSentCount(1);
    }

    public function test_resend_preserves_signed_https_verification_link()
    {
        $this->customer->forceFill(['email_verified_at' => null])->save();
        $this->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->post('http://phoneshop-bwkm.onrender.com/email/resend')
            ->assertSessionHas('resent', true);

        $payload = Http::recorded()->first()[0]->data();
        $this->assertSame($this->customer->email, $payload['to'][0]['email']);
        $this->assertStringContainsString('signature=', $payload['htmlContent']);
        $this->assertSame(1, preg_match('~https://phoneshop-bwkm\.onrender\.com/email/verify/[^\s]+~', $payload['textContent'], $matches));
        $this->get($matches[0])->assertRedirect('/email/verified');
        $this->assertTrue($this->customer->fresh()->hasVerifiedEmail());
        Http::assertSentCount(1);
    }

    public function test_password_reset_email_contains_a_working_https_reset_token()
    {
        $this->post('/logout');
        $this->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->post('http://phoneshop-bwkm.onrender.com/password/email', ['email' => $this->customer->email])
            ->assertSessionHas('status');

        $payload = Http::recorded()->first()[0]->data();
        $this->assertSame($this->customer->email, $payload['to'][0]['email']);
        $this->assertSame(1, preg_match('~https://phoneshop-bwkm\.onrender\.com/password/reset/[^\s]+~', $payload['textContent'], $matches));
        $url = $matches[0];
        $token = basename(parse_url($url, PHP_URL_PATH));
        $this->assertTrue(Password::broker()->tokenExists($this->customer, $token));
        $this->assertStringContainsString($token, $payload['htmlContent']);
        $this->get($url)->assertOk();
        $this->post('https://phoneshop-bwkm.onrender.com/password/reset', [
            'email' => $this->customer->email, 'token' => $token,
            'password' => 'NewPassword2026!', 'password_confirmation' => 'NewPassword2026!',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPassword2026!', $this->customer->fresh()->password));
        Http::assertSentCount(1);
    }

    public function test_html_alternative_recipients_and_attachments_are_preserved()
    {
        $message = $this->message()
            ->setCc(['cc@example.net' => 'CC Customer'])
            ->setBcc(['bcc@example.net' => null])
            ->setReplyTo(['support@tdhphone.vn' => 'TDH Support'])
            ->addPart('<p>Hello</p>', 'text/html')
            ->attach(new \Swift_Attachment('invoice contents', 'invoice.txt', 'text/plain'));
        $plugin = \Mockery::mock(\Swift_Events_SendListener::class);
        $plugin->shouldReceive('beforeSendPerformed')->once();
        $plugin->shouldReceive('sendPerformed')->once();
        $transport = $this->transport();
        $transport->registerPlugin($plugin);

        $this->assertSame(3, $transport->send($message));
        $this->assertSame('<test-message@brevo.test>', $message->getHeaders()->get('X-Brevo-Message-ID')->getFieldBody());
        $this->assertSame(['bcc@example.net' => null], $message->getBcc());
        Http::assertSent(function ($request) {
            return $request['htmlContent'] === '<p>Hello</p>' && $request['textContent'] === 'Hello'
                && $request['cc'] === [['email' => 'cc@example.net', 'name' => 'CC Customer']]
                && $request['bcc'] === [['email' => 'bcc@example.net']]
                && $request['replyTo'] === ['email' => 'support@tdhphone.vn', 'name' => 'TDH Support']
                && $request['attachment'] === [['name' => 'invoice.txt', 'content' => base64_encode('invoice contents')]];
        });
        Http::assertSentCount(1);
    }

    public function test_missing_api_key_fails_before_sending()
    {
        config(['services.brevo.key' => '  ']);
        app('mail.manager')->purge('brevo');
        try {
            $this->transport()->send($this->message());
            $this->fail('Missing API key must not succeed.');
        } catch (\Swift_TransportException $exception) {
            $this->assertStringContainsString('BREVO_API_KEY', $exception->getMessage());
        }
        Http::assertNothingSent();
    }

    /** @dataProvider unsuccessfulResponses */
    public function test_rejected_or_invalid_response_is_not_reported_as_sent($body, int $status)
    {
        Http::swap(new Factory());
        Http::fake(['*' => Http::response($body, $status)]);
        $transport = $this->transport();
        $plugin = \Mockery::mock(\Swift_Events_SendListener::class);
        $plugin->shouldReceive('beforeSendPerformed')->once();
        $plugin->shouldNotReceive('sendPerformed');
        $transport->registerPlugin($plugin);
        try {
            $transport->send($this->message());
            $this->fail('A rejected or malformed response must not succeed.');
        } catch (\Swift_TransportException $exception) {
            $this->assertStringContainsString('Brevo', $exception->getMessage());
            $this->assertStringNotContainsString('TEST_BREVO_KEY_DO_NOT_PRINT', (string) $exception);
            $this->assertNull($exception->getPrevious());
            if ($status >= 300) {
                $this->assertStringContainsString('HTTP ' . $status, $exception->getMessage());
            }
        }
        Http::assertSentCount(1);
    }

    public static function unsuccessfulResponses(): array
    {
        return [
            'unauthorized' => [['message' => 'TEST_BREVO_KEY_DO_NOT_PRINT'], 401],
            'unverified sender' => [['code' => 'invalid_parameter'], 400],
            'forbidden' => [[], 403],
            'quota' => [[], 429],
            'server error' => [[], 500],
            'redirect' => [[], 302],
            'missing id' => [[], 201],
            'invalid json' => ['not JSON', 201],
            'empty id' => [['messageId' => ''], 201],
            'invalid id type' => [['messageId' => ['unexpected']], 201],
        ];
    }

    public function test_connection_failure_is_sanitized_and_not_retried()
    {
        $attempts = 0;
        Http::swap(new Factory());
        Http::fake(function () use (&$attempts) {
            $attempts++;
            throw new ConnectionException('TEST_BREVO_KEY_DO_NOT_PRINT /password/reset/PRIVATE_TOKEN');
        });
        try {
            $this->transport()->send($this->message());
            $this->fail('Connection failure must not succeed.');
        } catch (\Swift_TransportException $exception) {
            $this->assertStringContainsString('Unable to connect to Brevo', $exception->getMessage());
            $this->assertStringNotContainsString('TEST_BREVO_KEY_DO_NOT_PRINT', (string) $exception);
            $this->assertStringNotContainsString('PRIVATE_TOKEN', (string) $exception);
            $this->assertNull($exception->getPrevious());
        }
        $this->assertSame(1, $attempts);
    }

    public function test_inline_attachments_fail_instead_of_silently_disappearing()
    {
        $message = $this->message()->attach(new \Swift_Image('image data', 'logo.png', 'image/png'));
        try {
            $this->transport()->send($message);
            $this->fail('Unsupported inline attachment must not silently disappear.');
        } catch (\Swift_TransportException $exception) {
            $this->assertStringContainsString('Inline email attachments', $exception->getMessage());
        }
        Http::assertNothingSent();
    }

    private function transport(): BrevoTransport
    {
        return Mail::mailer('brevo')->getSwiftMailer()->getTransport();
    }

    private function message(): \Swift_Message
    {
        return (new \Swift_Message('Test message'))
            ->setFrom(['store@tdhphone.vn' => 'TDH Phone'])
            ->setTo(['customer@example.net' => 'Customer'])
            ->setBody('Hello', 'text/plain');
    }
}
