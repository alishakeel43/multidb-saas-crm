<?php

namespace App\Support;

use InvalidArgumentException;

class TenantIdentifier
{
    public const RESERVED_NAMES = [
        'www',
        'admin',
        'super-admin',
        'api',
        'mail',
        'static',
        'assets',
        'landlord',
        'platform',
    ];

    public static function validateSlug(string $slug): string
    {
        if (
            strlen($slug) > 57
            || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)
            || in_array($slug, self::RESERVED_NAMES, true)
        ) {
            throw new InvalidArgumentException(
                'Tenant slug must be lowercase alphanumeric with optional hyphens, up to 57 characters, and not reserved.'
            );
        }

        return $slug;
    }

    public static function validateDomain(string $domain): string
    {
        $domain = strtolower(rtrim(trim($domain), '.'));
        $labels = explode('.', $domain);

        if (
            strlen($domain) > 255
            || count($labels) < 2
            || in_array($labels[0], self::RESERVED_NAMES, true)
        ) {
            throw new InvalidArgumentException('Tenant domain is invalid or uses a reserved subdomain.');
        }

        foreach ($labels as $label) {
            if (
                strlen($label) > 63
                || ! preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $label)
            ) {
                throw new InvalidArgumentException('Tenant domain must contain valid DNS host labels only.');
            }
        }

        return $domain;
    }

    public static function domainForSlug(string $slug): string
    {
        $slug = self::validateSlug($slug);
        $suffix = trim((string) config('multitenancy.tenant_domain_suffix', ''));

        if ($suffix !== '') {
            return self::validateDomain($slug.$suffix);
        }

        return self::validateDomain($slug.'.'.config('multitenancy.tenant_base_domain', 'localhost'));
    }

    public static function databaseName(string $slug): string
    {
        return 'tenant_'.self::validateSlug($slug);
    }
}
