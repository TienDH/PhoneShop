<?php

namespace App\Console\Commands;

use App\Services\GHNService;
use Illuminate\Console\Command;

class CheckIntegrations extends Command
{
    protected $signature = 'integrations:check
        {--probe-ghn : Read GHN provinces; does not create a shipment}
        {--district= : Optional GHN destination district for a fee quote}
        {--ward= : Optional GHN destination ward for a fee quote}';

    protected $description = 'Check deployment configuration without printing secrets or sending email/payments.';

    public function handle(): int
    {
        $rows = [];
        $add = function ($service, $status, $message) use (&$rows) {
            $rows[] = [$service, $status, $message];
        };
        $render = config('app.on_render');
        $url = rtrim((string) config('app.url'), '/');
        $add('APP_KEY', config('app.key') ? 'OK' : 'FAIL', config('app.key') ? 'Set (value hidden).' : 'APP_KEY is missing. Set one stable key; do not regenerate on restart.');
        $add('APP_URL', !$render || $this->isPublicHttps($url) ? 'OK' : 'FAIL', $render && !$this->isPublicHttps($url) ? 'Set APP_URL to the public HTTPS Render URL.' : 'Configured.');
        if ($render) {
            $add('HTTPS proxy', config('app.trusted_proxies') ? 'OK' : 'FAIL', config('app.trusted_proxies') ? 'Forwarded HTTPS is trusted.' : 'Set TRUSTED_PROXIES=* on Render.');
            $add('Session', config('session.secure') ? 'OK' : 'WARN', config('session.secure') ? 'Secure cookies enabled.' : 'Set SESSION_SECURE_COOKIE=true.');
            if (config('session.driver') === 'file') $add('Session', 'WARN', 'File sessions are lost on restart; use database sessions for persistent carts.');
            if (config('session.driver') === 'cookie') $add('Session', 'WARN', 'Cookie sessions have a small size limit; product images/cart snapshots can exceed it.');
            if (config('app.debug')) $add('Debug', 'WARN', 'Set APP_DEBUG=false before exposing the service.');
            if (config('logging.default') !== 'stderr') $add('Logs', 'WARN', 'Set LOG_CHANNEL=stderr to see Laravel failures in Render Logs.');
        }

        $mailer = config('mail.default');
        if ($render && $mailer === 'smtp' && in_array((int) config('mail.mailers.smtp.port'), [25, 465, 587], true)) {
            $add('Mail', 'FAIL', 'Render Free blocks SMTP ports 25/465/587. Use an HTTPS mail API or a paid instance.');
        } elseif (in_array($mailer, ['log', 'array'], true)) {
            $add('Mail', 'FAIL', 'MAIL_MAILER=' . $mailer . ' does not deliver email.');
        } elseif ($mailer === 'mailgun') {
            $this->required($add, 'Mail', ['MAILGUN_DOMAIN' => config('services.mailgun.domain'), 'MAILGUN_SECRET' => config('services.mailgun.secret')]);
            $add('Mail', 'INFO', 'Mailgun uses HTTPS; sender/domain and sandbox recipients must be verified with Mailgun.');
        } elseif ($mailer === 'postmark') {
            $this->required($add, 'Mail', ['POSTMARK_TOKEN' => config('services.postmark.token')]);
            if (!class_exists('Swift_PostmarkTransport')) $add('Mail', 'FAIL', 'Postmark transport package is not installed for Laravel 8.');
            $add('Mail', 'INFO', 'Postmark additionally requires its Laravel 8 transport package and a verified sender.');
        } else {
            $add('Mail', 'INFO', 'Mailer: ' . $mailer . '. Delivery has not been tested.');
        }
        $sender = config('mail.from.address');
        if (!$sender || !filter_var($sender, FILTER_VALIDATE_EMAIL) || str_ends_with($sender, '@example.com')) {
            $add('Mail sender', 'FAIL', 'Set MAIL_FROM_ADDRESS to an address verified by your email provider.');
        }

        $this->required($add, 'GHN', [
            'GHN_TOKEN' => config('ghn.token'), 'GHN_SHOP_ID' => (int) config('ghn.shop_id'),
            'GHN_FROM_DISTRICT_ID' => (int) config('ghn.from_district_id'),
        ]);
        $add('GHN environment', 'INFO', str_contains((string) config('ghn.base_url'), 'dev-online-gateway') ? 'Sandbox: token, shop and warehouse must all belong to sandbox.' : 'Check that token, shop and warehouse belong to the configured environment.');
        if (!config('ghn.verify_ssl')) $add('GHN TLS', 'WARN', 'Set GHN_VERIFY_SSL=true. Do not disable certificate verification in production.');

        $this->required($add, 'MoMo', [
            'MOMO_PARTNER_CODE' => config('services.momo.partner_code'),
            'MOMO_ACCESS_KEY' => config('services.momo.access_key'),
            'MOMO_SECRET_KEY' => config('services.momo.secret_key'),
        ]);
        foreach (['redirect_url' => '/payment/momo/callback', 'ipn_url' => '/payment/momo/ipn'] as $key => $path) {
            $target = config('services.momo.' . $key) ?: $url . $path;
            $valid = !$render || $this->isPublicHttps($target);
            $add('MoMo ' . $key, $valid ? 'OK' : 'FAIL', $valid ? 'Configured; verify the URL points to this service.' : 'Set a public HTTPS URL, not localhost or an HTTP URL.');
        }
        if (!config('services.momo.verify_ssl')) $add('MoMo TLS', 'WARN', 'Set MOMO_VERIFY_SSL=true.');
        $add('MoMo', 'INFO', 'Configuration only: no payment created, no credentials validated with MoMo.');

        if ($this->option('probe-ghn')) {
            $ghn = app(GHNService::class);
            $provinces = $ghn->getProvinces();
            $add('GHN provinces', $provinces ? 'OK' : 'FAIL', $provinces ? count($provinces) . ' provinces received.' : 'No data returned. Inspect GHN HTTP status in Laravel/Render Logs.');
            if ($this->option('district') && $this->option('ward')) {
                $quote = $ghn->calculateFee((int) $this->option('district'), (string) $this->option('ward'), 200, 100000);
                $add('GHN fee', !empty($quote['success']) ? 'OK' : 'FAIL', !empty($quote['success']) ? 'Quote received; no shipment created.' : 'Quote failed; verify shop, warehouse and destination in the same environment.');
            }
        }
        $this->table(['Integration', 'Status', 'Details (no secrets)'], $rows);
        return collect($rows)->contains(fn ($row) => $row[1] === 'FAIL') ? 1 : 0;
    }

    private function required(callable $add, string $service, array $values): void
    {
        $missing = array_keys(array_filter($values, fn ($value) => !$value));
        $add($service, $missing ? 'FAIL' : 'OK', $missing ? 'Missing: ' . implode(', ', $missing) : 'Required values are set (hidden).');
    }

    private function isPublicHttps(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        return parse_url($url, PHP_URL_SCHEME) === 'https' && is_string($host)
            && $host !== 'localhost' && !filter_var($host, FILTER_VALIDATE_IP)
            && str_contains($host, '.') && !str_ends_with($host, '.test') && !str_ends_with($host, '.local');
    }
}
