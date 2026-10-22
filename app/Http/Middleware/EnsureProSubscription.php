<?php

namespace App\Http\Middleware;

use App\Services\Billing\EntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProSubscription
{
    public function __construct(
        protected EntitlementService $entitlement
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $feature = null): Response
    {
        $vendor = auth('vendor')->user()?->vendor;

        $hasAccess = $feature
            ? $this->entitlement->canUse($vendor, $feature)
            : $this->entitlement->isPro($vendor);

        if (! $vendor || ! $hasAccess) {
            $featureName = $feature ?? 'pro_access';
            $featureLabel = match ($feature) {
                'export_reports' => 'Ekspor Laporan Transaksi',
                'custom_wa_templates' => 'Kustom Template WhatsApp',
                'unlimited_items' => 'Katalog Barang Tanpa Batas',
                'white_label' => 'Branding Toko White-Label',
                default => 'Fitur Premium',
            };

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => "Fitur {$featureLabel} khusus untuk vendor Pro. Silakan upgrade paket langganan Anda.",
                    'code' => 'UPGRADE_REQUIRED',
                    'error_code' => 'PLAN_REQUIRED',
                    'feature' => $featureName,
                ], 403);
            }

            return redirect()->route('vendor.billing.index')->with(
                'error',
                "Fitur {$featureLabel} khusus untuk vendor Pro. Silakan upgrade paket langganan Anda untuk membuka akses."
            );
        }

        return $next($request);
    }
}
