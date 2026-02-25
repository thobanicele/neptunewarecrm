<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EnvAudit extends Command
{
    protected $signature = 'env:audit';
    protected $description = 'Audit required production env/config values (masked)';

    public function handle(): int
    {
        $checks = [
            'APP_ENV' => fn() => config('app.env'),
            'APP_KEY' => fn() => config('app.key'),
            'APP_URL' => fn() => config('app.url'),

            // Turnstile
            'TURNSTILE_SITE_KEY' => fn() => config('services.turnstile.site_key'),
            'TURNSTILE_SECRET_KEY' => fn() => config('services.turnstile.secret_key'),

            // Common mail/db (adjust for your app)
            'DB_CONNECTION' => fn() => config('database.default'),
            'DB_HOST' => fn() => config('database.connections.' . config('database.default') . '.host'),
            'MAIL_MAILER' => fn() => config('mail.default'),
        ];

        foreach ($checks as $label => $getter) {
            $val = (string) ($getter() ?? '');
            $ok = $val !== '' ? 'OK' : 'MISSING';

            // mask output
            $masked = $val === '' ? '' : substr($val, 0, 6) . '...' . substr($val, -4);

            $this->line(sprintf('%-22s %-8s %s', $label, $ok, $masked));
        }

        return self::SUCCESS;
    }
}
