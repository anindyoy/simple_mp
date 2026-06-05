<?php

use Checkpoint\Checks;

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled Checks
    |--------------------------------------------------------------------------
    |
    | Every default check is listed here and enabled by default. Set any
    | entry to `false` to exclude it from `php artisan checkpoint:scan`.
    |
    | Checks not listed in this map fall back to enabled — so when you
    | upgrade Checkpoint and new checks are added, you keep the protection
    | without re-publishing this file.
    |
    */

    'checks' => [
        Checks\ComposerAuditCheck::class => true,
        Checks\NpmAuditCheck::class => true,
        Checks\EnvironmentCheck::class => true,
        Checks\GitIgnoreCheck::class => true,
        Checks\FilePermissionsCheck::class => true,
        Checks\HardcodedSecretsCheck::class => true,
        Checks\SqlInjectionCheck::class => true,
        Checks\MassAssignmentCheck::class => false,
        Checks\XssCheck::class => true,
        Checks\CsrfCheck::class => true,
        Checks\OpenRedirectCheck::class => true,
        Checks\CommandInjectionCheck::class => true,
        Checks\InsecureDeserializationCheck::class => true,
        Checks\DebugFunctionsCheck::class => true,
        Checks\SensitiveExposureCheck::class => true,
        Checks\SsrfCheck::class => true,
        Checks\TlsVerificationCheck::class => true,
        Checks\CorsConfigCheck::class => true,
        Checks\PackageFreshnessCheck::class => true,
        Checks\SuspiciousVendorAutoloadCheck::class => true,
        Checks\SupplyChainToolingCheck::class => true,
        Checks\PathTraversalCheck::class => true,
        Checks\WeakCryptographyCheck::class => true,
        Checks\InsecureRngCheck::class => true,
        Checks\SessionSecurityCheck::class => true,
        Checks\EolVersionCheck::class => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Freshness (Supply Chain)
    |--------------------------------------------------------------------------
    |
    | Composer packages released within `minimum_age_days` will fail the
    | "Package Freshness" check. This mitigates supply-chain attacks that
    | typically get caught and removed from Packagist within hours or days.
    |
    | Add fully-qualified package names to `whitelist` to bypass the age
    | check for specific dependencies (e.g. a critical security patch you
    | need to deploy before the freshness window expires).
    |
    */

    'package_freshness' => [
        'minimum_age_days' => 3,
        'whitelist' => [
            // Checkpoint exempts itself from the freshness gate so a fresh
            // release of the scanner cannot block its own user's deploy.
            'andreapollastri/checkpoint',
            // 'vendor/package',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Suspicious Vendor Autoload
    |--------------------------------------------------------------------------
    |
    | The "Suspicious Vendor Autoload" check warns when a package under
    | vendor/ registers PHP files via `autoload.files` — the exact mechanism
    | abused by the May 2026 Laravel-Lang supply-chain attack to execute
    | code on every request.
    |
    | A baked-in whitelist already covers packages that legitimately use
    | this mechanism (laravel/framework, symfony/polyfill-*, guzzlehttp/*,
    | ramsey/uuid, …). Add your own trusted entries below — exact matches
    | or `vendor/*` wildcards are both supported.
    |
    */

    'suspicious_autoload' => [
        'whitelist' => [
            'blade-ui-kit/blade-icons',
            'danharrin/date-format-converter',
            'filament/notifications',
            'fruitcake/laravel-debugbar',
            'lab404/laravel-impersonate',
            'laravel/prompts',
            'league/csv',
            'mockery/mockery',
            'myclabs/deep-copy',
            'nunomaduro/collision',
            'nunomaduro/termwind',
            'pestphp/pest',
            'pestphp/pest-plugin-arch',
            'pestphp/pest-plugin-livewire',
            'phpunit/phpunit',
            'pragmarx/google2fa',
            'propaganistas/laravel-phone',
            'psy/psysh',
            'ralouphie/getallheaders',
            'scrivo/highlight.php',
            'sebastian/global-state',
            'sebastian/type',
            'spatie/image-optimizer',
            'spatie/invade',
            'symfony/clock',
            'symfony/var-dumper',
            'filament/filament',
            'filament/forms',
            'filament/support',
            'livewire/livewire',
            'symfony/string',
            'symfony/translation',
            'spatie/laravel-activitylog',

        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Suppressed Findings
    |--------------------------------------------------------------------------
    |
    | Add 12-character finding hashes here to silence specific FAIL/WARN
    | issues you have intentionally accepted (false positive, legacy code,
    | etc.). Hashes are shown in square brackets next to each finding when
    | you run the scan — copy the bracketed value into this array.
    |
    | The hash is content-stable: refactors that only shift line numbers
    | will not invalidate it.
    |
    | If every finding of a check is suppressed, the check is downgraded to
    | PASS with an explicit "N suppressed" message.
    |
    */

    'suppressed' => [
        'e139cf01f8af',
        '9c1241bc6dfb', // CekQueryCommand.php — dump-and-die intentional, debug-only command
        'd31b3e0cb7e9', // rules/index.blade.php — {!! !!} safe: content only writable by admin
        'fc44fc869c16', // .env perms 666 — Windows NTFS, not real Unix permissions
        'ea61233e43a4', // .env world-writable — Windows NTFS, not real Unix permissions
        'd3fe1df47182', // storage/ 777 — Windows NTFS, not real Unix permissions
        '11237ebcced3', // Supply chain tooling — Socket CLI installed in CI workflow
        'ef474f32494e', // Supply chain tooling — install Safe-Chain recommendation
        '2e20e774eab1', // Supply chain tooling — safe-chain setup instruction
        '32cc17ad5d23', // Supply chain tooling — alternative Socket CLI recommendation
    ],

];
