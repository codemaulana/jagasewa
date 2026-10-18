<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Keys containing PII or sensitive data that must never be recorded.
     *
     * @var list<string>
     */
    protected const SENSITIVE_KEYS = [
        'nik',
        'wa_number',
        'phone',
        'phone_number',
        'password',
        'password_confirmation',
        'raw_file_path',
        'storage_path',
        'file_path',
        'card_number',
        'cvv',
        'secret',
        'token',
        'remember_token',
        'cookie',
    ];

    /**
     * Record an audit event into audit_logs table with automatic PII sanitization.
     */
    public static function log(
        string $event,
        string $entityType,
        string $entityId,
        ?array $payload = null,
        ?int $vendorId = null,
        ?int $userId = null
    ): AuditLog {
        $vendorUser = Auth::guard('vendor')->user();

        $resolvedVendorId = $vendorId ?? $vendorUser?->vendor_id ?? session('vendor_id');
        $resolvedUserId = $userId ?? $vendorUser?->id;
        $ipAddress = request()?->ip();

        $sanitizedPayload = $payload ? static::sanitizePayload($payload) : null;

        return AuditLog::create([
            'vendor_id' => $resolvedVendorId,
            'user_id' => $resolvedUserId,
            'event' => $event,
            'entity_type' => $entityType,
            'entity_id' => (string) $entityId,
            'payload' => $sanitizedPayload,
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);
    }

    /**
     * Recursively sanitize payload to strip any PII or sensitive data.
     */
    public static function sanitizePayload(array $payload): array
    {
        $sanitized = [];

        foreach ($payload as $key => $value) {
            $lowerKey = strtolower((string) $key);

            // Check if key contains any sensitive pattern
            $isSensitive = false;
            foreach (static::SENSITIVE_KEYS as $sensitiveKey) {
                if (str_contains($lowerKey, $sensitiveKey)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = static::sanitizePayload($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
