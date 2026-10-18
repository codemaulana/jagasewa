<?php

namespace App\Services;

use InvalidArgumentException;

class IdentityHashService
{
    /**
     * Supported identity types.
     */
    public const TYPE_WA = 'wa';

    public const TYPE_NIK = 'nik';

    public const TYPE_INSTAGRAM = 'instagram';

    /**
     * Normalize a phone number to standard Indonesian format (628...).
     */
    public static function normalizePhone(string $phone): string
    {
        $cleaned = preg_replace('/\D/', '', $phone);

        if (str_starts_with($cleaned, '08')) {
            $cleaned = '628'.substr($cleaned, 2);
        } elseif (str_starts_with($cleaned, '8')) {
            $cleaned = '62'.$cleaned;
        }

        return $cleaned;
    }

    /**
     * Normalize a NIK by stripping all non-digit characters.
     */
    public static function normalizeNik(string $nik): string
    {
        return preg_replace('/\D/', '', $nik);
    }

    /**
     * Normalize an Instagram handle (lowercase, strips leading @, URL prefixes, and trailing slash).
     */
    public static function normalizeInstagram(string $instagram): string
    {
        $clean = trim($instagram);

        if (str_contains($clean, '?')) {
            $clean = explode('?', $clean, 2)[0];
        }

        if (str_contains($clean, '#')) {
            $clean = explode('#', $clean, 2)[0];
        }

        $clean = preg_replace('#^(https?://)?(www\.)?instagram\.com/#i', '', $clean);
        $clean = ltrim($clean, '@/');
        $clean = rtrim($clean, '/');

        return strtolower(trim($clean));
    }

    /**
     * Normalize an identity value according to its type.
     *
     * @throws InvalidArgumentException
     */
    public static function normalize(string $type, string $value): string
    {
        return match (strtolower(trim($type))) {
            'wa', 'phone', 'whatsapp' => self::normalizePhone($value),
            'nik' => self::normalizeNik($value),
            'instagram', 'ig' => self::normalizeInstagram($value),
            default => throw new InvalidArgumentException("Unsupported identity type: {$type}"),
        };
    }

    /**
     * Compute HMAC-SHA256 blind index hash using BLACKLIST_SECRET or APP_KEY.
     */
    public static function hashValue(string $value, ?string $secret = null): string
    {
        $resolvedSecret = $secret ?? config('app.blacklist_secret') ?: config('app.key');

        return hash_hmac('sha256', $value, (string) $resolvedSecret);
    }

    /**
     * Normalize and hash an identity value in one operation.
     */
    public static function hashIdentity(string $type, string $value, ?string $secret = null): string
    {
        $normalized = self::normalize($type, $value);

        return self::hashValue($normalized, $secret);
    }

    /**
     * Verify if a raw identity matches a stored blind index hash.
     */
    public static function matches(string $type, string $rawValue, string $storedHash, ?string $secret = null): bool
    {
        $computedHash = self::hashIdentity($type, $rawValue, $secret);

        return hash_equals($storedHash, $computedHash);
    }
}
