<?php

namespace App\Services\Billing;

use App\Services\QrisService;

class QrisEngineClient
{
    protected QrisService $qrisService;

    public function __construct(?QrisService $qrisService = null)
    {
        $this->qrisService = $qrisService ?? app(QrisService::class);
    }

    /**
     * In-process native PHP service is always healthy.
     */
    public function isHealthy(): bool
    {
        return true;
    }

    /**
     * Generate dynamic QRIS invoice natively within Laravel without external network calls.
     *
     * @return array<string, mixed>|null
     */
    public function createDynamicQris(
        string $referenceCode,
        int $amount,
        string $renterName,
        int $vendorId,
        int $expiredMinutes = 30
    ): ?array {
        return $this->qrisService->generateDynamicQris(
            amount: $amount,
            referenceCode: $referenceCode,
            merchantName: 'JAGASEWA',
            city: 'JAKARTA',
            expiredMinutes: $expiredMinutes
        );
    }

    /**
     * Check payment status natively from database.
     *
     * @return array<string, mixed>|null
     */
    public function getStatus(string $referenceCode): ?array
    {
        $isSettled = $this->qrisService->isSettled($referenceCode);

        return [
            'success' => true,
            'status' => $isSettled ? 'settled' : 'pending',
            'reference_code' => $referenceCode,
        ];
    }
}
