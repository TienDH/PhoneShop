<?php

namespace Tests\Feature;

use App\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Tests\StoreTestCase;

class RenderDeploymentTest extends StoreTestCase
{
    protected function tearDown(): void
    {
        Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_PROTO);
        parent::tearDown();
    }

    public function test_render_proxy_generates_https_login_and_shipping_urls()
    {
        config(['app.trusted_proxies' => '*']);
        $this->post('/logout');
        $this->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://phoneshop-bwkm.onrender.com/login')->assertOk()
            ->assertSee('action="https://phoneshop-bwkm.onrender.com/login"', false)
            ->assertDontSee('http://phoneshop-bwkm.onrender.com/login', false);
        $request = Request::create('http://phoneshop-bwkm.onrender.com/checkout', 'GET', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.2', 'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'untrusted.example.com',
        ]);
        app(TrustProxies::class)->handle($request, function ($request) {
            $this->assertTrue($request->isSecure());
            $this->assertSame('phoneshop-bwkm.onrender.com', $request->getHost());
            return response('OK');
        });
    }

    public function test_forwarded_headers_are_not_trusted_on_local_without_configuration()
    {
        config(['app.trusted_proxies' => null]);
        $request = Request::create('http://localhost/login', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1', 'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);
        app(TrustProxies::class)->handle($request, function ($request) {
            $this->assertFalse($request->isSecure());
            return response('OK');
        });
    }

    public function test_admin_login_redirect_uses_https_behind_render_proxy()
    {
        config(['app.trusted_proxies' => '*']);
        $this->post('/logout');
        $this->admin->update(['password' => Hash::make('ProxyTest2026!')]);
        $this->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->post('http://phoneshop-bwkm.onrender.com/login', ['email' => $this->admin->email, 'password' => 'ProxyTest2026!'])
            ->assertRedirect('https://phoneshop-bwkm.onrender.com/admin/dashboard');
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_signed_verification_links_validate_behind_https_proxy()
    {
        config(['app.trusted_proxies' => '*']);
        $user = $this->customer;
        $user->forceFill(['email_verified_at' => null])->save();
        Route::middleware('web')->get('/_test-verification-link', function () use ($user) {
            return response()->json(['url' => URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
                'id' => $user->id, 'hash' => sha1($user->email),
            ])]);
        });
        $url = $this->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://phoneshop-bwkm.onrender.com/_test-verification-link')->assertOk()->json('url');
        $this->assertStringStartsWith('https://phoneshop-bwkm.onrender.com/', $url);
        $this->get(str_replace('https://', 'http://', $url))->assertRedirect('/email/verified');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_diagnostics_report_missing_configuration_and_hide_secret_values()
    {
        $this->withoutMockingConsoleOutput();
        config([
            'app.on_render' => true, 'app.url' => 'http://localhost', 'app.trusted_proxies' => null,
            'mail.default' => 'smtp', 'mail.mailers.smtp.port' => 587, 'mail.mailers.smtp.password' => 'DO_NOT_PRINT_PASSWORD',
            'ghn.token' => 'DO_NOT_PRINT_TOKEN', 'services.momo.secret_key' => '',
        ]);
        $this->assertSame(1, Artisan::call('integrations:check'));
        $output = Artisan::output();
        $this->assertStringContainsString('Render Free blocks SMTP', $output);
        $this->assertStringContainsString('MOMO_SECRET_KEY', $output);
        $this->assertStringContainsString('GHN_SHOP_ID', $output);
        $this->assertStringNotContainsString('DO_NOT_PRINT_PASSWORD', $output);
        $this->assertStringNotContainsString('DO_NOT_PRINT_TOKEN', $output);
        Http::assertNothingSent();
    }

    public function test_valid_https_mailgun_configuration_passes_without_external_requests()
    {
        $this->withoutMockingConsoleOutput();
        config([
            'app.on_render' => true, 'app.url' => 'https://phoneshop-bwkm.onrender.com', 'app.trusted_proxies' => '*',
            'session.secure' => true, 'mail.default' => 'mailgun', 'mail.from.address' => 'store@tdhphone.vn',
            'services.mailgun.domain' => 'tdhphone.vn', 'services.mailgun.secret' => 'DO_NOT_PRINT_MAILGUN',
            'ghn.token' => 'DO_NOT_PRINT_TOKEN', 'ghn.shop_id' => 1, 'ghn.from_district_id' => 1,
        ]);
        $this->assertSame(0, Artisan::call('integrations:check'));
        $this->assertStringNotContainsString('DO_NOT_PRINT_MAILGUN', Artisan::output());
        Http::assertNothingSent();
    }
}
