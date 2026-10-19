<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Subscription;
use App\Services\Billing\EntitlementService;
use App\Services\Billing\MayarPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BillingController extends Controller
{
    public function __construct(
        protected EntitlementService $entitlement
    ) {}

    /**
     * Display current subscription status, limits, and plan options.
     */
    public function index(Request $request)
    {
        $vendor = auth('vendor')->user()?->vendor;

        if (! $vendor) {
            abort(403);
        }

        $subscription = $vendor->currentSubscription;
        $isPro = $this->entitlement->isPro($vendor);
        $activeItemsCount = Item::where('vendor_id', $vendor->id)->where('is_active', true)->count();
        $blacklistCount = $vendor->blacklistEntries()->count();

        $data = [
            'vendor' => $vendor,
            'subscription' => $subscription,
            'is_pro' => $isPro,
            'limits' => [
                'active_items' => [
                    'used' => $activeItemsCount,
                    'max' => $isPro ? 'Unlimited' : EntitlementService::MAX_FREE_ACTIVE_ITEMS,
                    'can_add' => $this->entitlement->canAddActiveItem($vendor),
                ],
                'blacklist_entries' => [
                    'used' => $blacklistCount,
                    'max' => $isPro ? 'Unlimited' : EntitlementService::MAX_FREE_BLACKLIST_ENTRIES,
                    'can_add' => $this->entitlement->canAddBlacklistEntry($vendor),
                ],
                'white_label' => $this->entitlement->hasWhiteLabel($vendor),
                'reports_export' => $this->entitlement->canExportReports($vendor),
            ],
            'pricing' => [
                'monthly' => 15000,
                'quarterly' => 39000,
                'annually' => 129000,
            ],
        ];

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($data);
        }

        return view('vendor.billing.index', $data);
    }

    /**
     * Export booking and revenue report (Pro-only feature).
     * Protected by 'vendor.pro' middleware.
     */
    public function exportReports(Request $request): StreamedResponse
    {
        $vendor = auth('vendor')->user()?->vendor;

        if (! $vendor) {
            abort(403);
        }

        $filename = 'laporan-booking-jagasewa-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($vendor) {
            $handle = fopen('php://output', 'w');

            // Header CSV
            fputcsv($handle, [
                'Kode Booking',
                'Nama Barang',
                'Tanggal Mulai',
                'Tanggal Selesai',
                'Status',
                'Total Harga (Rp)',
                'Deposit (Rp)',
            ]);

            Booking::where('vendor_id', $vendor->id)
                ->with('item')
                ->chunk(100, function ($bookings) use ($handle) {
                    foreach ($bookings as $booking) {
                        fputcsv($handle, [
                            $booking->reference_code,
                            $booking->item?->name ?? '-',
                            $booking->start_date?->format('Y-m-d'),
                            $booking->end_date?->format('Y-m-d'),
                            $booking->status,
                            $booking->total_price,
                            $booking->deposit_amount,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Initiate checkout for Pro subscription tier via Mayar.id (Pilihan A).
     */
    public function checkout(Request $request, MayarPaymentService $paymentService): RedirectResponse
    {
        $request->validate([
            'cycle' => ['required', 'string', 'in:monthly,quarterly,annually'],
        ]);

        $vendor = auth('vendor')->user()?->vendor;

        if (! $vendor) {
            abort(403);
        }

        $cycle = $request->input('cycle');

        /** @var Subscription $subscription */
        $subscription = Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => $cycle,
            'current_period_start' => now('Asia/Jakarta'),
        ]);

        $user = auth('vendor')->user();

        try {
            $result = $paymentService->createPaymentLink($subscription, $user);

            return redirect()->away($result['payment_url']);
        } catch (\Exception $e) {
            $subscription->delete();

            return redirect()->route('vendor.billing.index')->with('error', $e->getMessage());
        }
    }
}
