<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillingReturnController extends Controller
{
    /**
     * Handle browser redirect return from hosted checkout (BLT-06 / SEC-22).
     *
     * IMPORTANT: This method MUST NOT alter any subscription status.
     * The single source of truth for payment status is the verified webhook.
     */
    public function finish(Request $request): RedirectResponse
    {
        return redirect()->to('/vendor')->with(
            'status',
            'Pembayaran Anda sedang diverifikasi. Fitur Pro akan aktif otomatis setelah konfirmasi diterima dari payment gateway.'
        );
    }
}
