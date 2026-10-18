<?php

namespace App\Http\Middleware;

use App\Models\VendorUser;
use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnsureVendorOwnership
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var VendorUser|null $user */
        $user = Auth::guard('vendor')->user();

        if (! $user) {
            if ($request->expectsJson()) {
                abort(401, 'Unauthenticated.');
            }

            return redirect()->guest('/vendor/login');
        }

        // Check if the vendor is active
        if (! $user->vendor || ! $user->vendor->is_active) {
            Auth::guard('vendor')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(403, 'Akun Anda dinonaktifkan. Hubungi support.');
            }

            return redirect('/vendor/login')->withErrors([
                'email' => 'Akun Anda dinonaktifkan. Hubungi support.',
            ]);
        }

        // Ensure session vendor_id is bound
        if (session('vendor_id') !== $user->vendor_id) {
            session(['vendor_id' => $user->vendor_id]);
        }

        // Inspect route parameters to prevent IDOR
        foreach ($request->route()->parameters() as $key => $parameter) {
            if (is_object($parameter) && isset($parameter->vendor_id)) {
                if ((int) $parameter->vendor_id !== (int) $user->vendor_id) {
                    AuditService::log(
                        'idor_attempt_blocked',
                        $parameter::class,
                        (string) ($parameter->id ?? 'unknown'),
                        ['target_vendor_id' => $parameter->vendor_id]
                    );

                    throw new NotFoundHttpException('Resource not found.');
                }
            }

            if ($key === 'vendor_id' && is_scalar($parameter)) {
                if ((int) $parameter !== (int) $user->vendor_id) {
                    throw new NotFoundHttpException('Resource not found.');
                }
            }
        }

        return $next($request);
    }
}
