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
    public function handle(Request $request, Closure $next): Response
    {
        $vendor = auth('vendor')->user()?->vendor;

        if (! $vendor || ! $this->entitlement->isPro($vendor)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Fitur ini khusus untuk vendor Pro. Silakan upgrade paket langganan Anda.',
                    'code' => 'UPGRADE_REQUIRED',
                ], 403);
            }

            return redirect()->route('vendor.billing.index')->with(
                'error',
                'Fitur ini khusus untuk vendor Pro. Silakan upgrade paket langganan Anda untuk membuka akses.'
            );
        }

        return $next($request);
    }
}
