<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Subscription;
use App\Models\Vendor;
use App\Services\Billing\EntitlementService;
use App\Services\Billing\MayarPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $user = auth('vendor')->user();

        if (! $user || ! $user->vendor || ! $user->canManageSettings()) {
            abort(403, 'Akses ditolak. Hanya pemilik toko (owner) yang dapat mengelola tagihan dan paket langganan.');
        }

        $vendor = $user->vendor;

        $subscription = $vendor->currentSubscription;
        $activeSubscription = $vendor->activeSubscription;
        $pendingSubscription = $vendor->pendingSubscription;
        $isPro = $this->entitlement->isPro($vendor);
        $activeItemsCount = Item::where('vendor_id', $vendor->id)->where('is_active', true)->count();
        $blacklistCount = $vendor->blacklistEntries()->count();
        $entitlements = $this->entitlement->getEntitlements($vendor);
        $paymentHistory = $vendor->subscriptions()
            ->with('manualActivations')
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        $data = [
            'vendor' => $vendor,
            'subscription' => $subscription,
            'active_subscription' => $activeSubscription,
            'pending_subscription' => $pendingSubscription,
            'is_pro' => $isPro,
            'in_grace_period' => $this->entitlement->isInGracePeriod($vendor),
            'is_near_expiration' => $this->entitlement->isNearExpiration($vendor),
            'days_until_expiration' => $this->entitlement->getDaysUntilExpiration($vendor),
            'entitlements' => $entitlements,
            'payment_history' => $paymentHistory,
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
     * Return current vendor entitlement, limits, and usage (Single Source of Truth for frontend).
     */
    public function myEntitlements(Request $request)
    {
        $vendor = auth('vendor')->user()?->vendor;

        if (! $vendor) {
            abort(403, 'Unauthorized');
        }

        return response()->json($this->entitlement->getEntitlements($vendor));
    }

    /**
     * Display upgrade confirmation screen showing unlocked Pro features.
     */
    public function success(Request $request)
    {
        $vendor = auth('vendor')->user()?->vendor;

        return view('vendor.billing.finish', [
            'vendor' => $vendor,
            'is_pro' => $vendor ? $this->entitlement->isPro($vendor) : false,
            'entitlements' => $vendor ? $this->entitlement->getEntitlements($vendor) : [],
        ]);
    }

    /**
     * Export booking and revenue report (Pro-only feature).
     * Protected by 'vendor.pro' middleware.
     */
    public function exportReports(Request $request): StreamedResponse
    {
        $user = auth('vendor')->user();

        if (! $user || ! $user->vendor || ! $user->canViewFinancials()) {
            abort(403, 'Akses ditolak. Staf operasional tidak memiliki akses ke laporan finansial.');
        }

        $vendor = $user->vendor;

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $suffix = ($startDate && $endDate) ? "_{$startDate}_sampai_{$endDate}" : '';
        $filename = 'laporan-booking-jagasewa'.$suffix.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($vendor, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');

            // Header CSV
            fputcsv($handle, [
                'Kode Booking',
                'Nama Penyewa',
                'Nama Barang',
                'Tanggal Mulai',
                'Tanggal Selesai',
                'Durasi (Hari)',
                'Status',
                'Total Harga (Rp)',
                'Deposit (Rp)',
            ]);

            $query = Booking::where('vendor_id', $vendor->id)
                ->with(['item', 'renter']);

            if (! empty($startDate)) {
                $query->whereDate('start_date', '>=', $startDate);
            }
            if (! empty($endDate)) {
                $query->whereDate('start_date', '<=', $endDate);
            }

            $query->chunk(100, function ($bookings) use ($handle) {
                foreach ($bookings as $booking) {
                    fputcsv($handle, [
                        $booking->reference_code,
                        $booking->renter?->name ?? 'Penyewa',
                        $booking->item?->name ?? '-',
                        $booking->start_date?->format('Y-m-d'),
                        $booking->end_date?->format('Y-m-d'),
                        $booking->total_days,
                        $booking->status,
                        $booking->total_price,
                        $booking->deposit_paid ?? $booking->item?->deposit_amount ?? 0,
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
     * Protected by pessimistic locking and single-active-subscription rule (T-45 / BUG-01).
     */
    public function checkout(Request $request, MayarPaymentService $paymentService)
    {
        $request->validate([
            'cycle' => ['required', 'string', 'in:monthly,quarterly,annually'],
            'is_renewal' => ['nullable', 'boolean'],
            'action' => ['nullable', 'string'],
        ]);

        $user = auth('vendor')->user();
        if (! $user || ! $user->vendor_id || ! $user->canManageSettings()) {
            abort(403, 'Akses ditolak. Hanya pemilik toko (owner) yang dapat mengelola tagihan dan paket langganan.');
        }

        $cycle = $request->input('cycle');
        $isRenewal = $request->boolean('is_renewal') || $request->input('action') === 'extend';

        return DB::transaction(function () use ($user, $cycle, $isRenewal, $paymentService, $request) {
            /** @var Vendor $vendor */
            $vendor = Vendor::where('id', $user->vendor_id)->lockForUpdate()->firstOrFail();

            // 1. Guard against double subscription if already Pro (unless explicitly renewing)
            if ($this->entitlement->isPro($vendor) && ! $isRenewal) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'error' => 'ALREADY_SUBSCRIBED',
                        'message' => 'Toko Anda sudah aktif berlangganan Paket Pro. Gunakan tombol perpanjangan langganan.',
                    ], 409);
                }

                $activeSub = $vendor->activeSubscription;
                $expiryText = $activeSub?->current_period_end
                    ? $activeSub->current_period_end->translatedFormat('d F Y')
                    : 'Aktif';

                return redirect()->route('vendor.billing.index')
                    ->with('error', "Toko Anda sudah aktif berlangganan Paket Pro (berlaku hingga {$expiryText}). Pembelian ganda tidak diizinkan. Silakan pilih perpanjang.");
            }

            // 2. Guard against duplicate pending subscriptions
            $pendingSub = Subscription::where('vendor_id', $vendor->id)
                ->where('status', Subscription::STATUS_PENDING_PAYMENT)
                ->lockForUpdate()
                ->latest()
                ->first();

            if ($pendingSub) {
                // If the pending order is the exact same billing cycle, reuse payment or avoid duplicate
                if ($pendingSub->billing_cycle === $cycle) {
                    if ($request->expectsJson() || $request->is('api/*')) {
                        return response()->json([
                            'error' => 'PENDING_ORDER_EXISTS',
                            'message' => 'Anda sudah memiliki tagihan pending untuk paket ini. Silakan selesaikan pembayaran.',
                            'subscription_ulid' => $pendingSub->ulid,
                        ], 409);
                    }

                    $mockInvoiceId = $pendingSub->gateway_subscription_id;
                    $paymentUrl = $mockInvoiceId && str_starts_with($mockInvoiceId, 'pay_mock_')
                        ? "https://sandbox.mayar.id/pay/{$mockInvoiceId}"
                        : null;

                    if ($paymentUrl) {
                        return redirect()->away($paymentUrl);
                    }

                    return redirect()->route('vendor.billing.index')
                        ->with('error', 'Anda sudah memiliki tagihan pending untuk paket ini. Silakan selesaikan pembayaran atau batalkan tagihan sebelum membuat yang baru.');
                }

                // If vendor deliberately chooses a different billing cycle, cancel previous pending order
                $pendingSub->update([
                    'status' => Subscription::STATUS_CANCELLED,
                ]);
            }

            // Calculate start date: if renewal of active sub, connect seamlessly from current_period_end
            $activeSub = $vendor->activeSubscription;
            $periodStart = ($isRenewal && $activeSub && $activeSub->current_period_end && $activeSub->current_period_end->isFuture())
                ? $activeSub->current_period_end
                : now('Asia/Jakarta');

            /** @var Subscription $subscription */
            $subscription = Subscription::create([
                'vendor_id' => $vendor->id,
                'plan' => Subscription::PLAN_PRO,
                'status' => Subscription::STATUS_PENDING_PAYMENT,
                'billing_cycle' => $cycle,
                'current_period_start' => $periodStart,
            ]);

            try {
                $result = $paymentService->createPaymentLink($subscription, $user);

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'status' => 'success',
                        'payment_url' => $result['payment_url'],
                        'subscription_ulid' => $subscription->ulid,
                    ]);
                }

                return redirect()->away($result['payment_url']);
            } catch (\Exception $e) {
                $subscription->delete();

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json(['error' => $e->getMessage()], 500);
                }

                return redirect()->route('vendor.billing.index')->with('error', $e->getMessage());
            }
        });
    }

    /**
     * Cancel an existing pending payment order so the vendor can choose another plan.
     */
    public function cancelPending(Request $request): RedirectResponse
    {
        $user = auth('vendor')->user();
        if (! $user || ! $user->vendor_id || ! $user->canManageSettings()) {
            abort(403, 'Akses ditolak. Hanya pemilik toko (owner) yang dapat mengelola tagihan dan paket langganan.');
        }

        return DB::transaction(function () use ($user) {
            $vendor = Vendor::where('id', $user->vendor_id)->lockForUpdate()->firstOrFail();

            $pendingSub = Subscription::where('vendor_id', $vendor->id)
                ->where('status', Subscription::STATUS_PENDING_PAYMENT)
                ->lockForUpdate()
                ->first();

            if ($pendingSub) {
                $pendingSub->update([
                    'status' => Subscription::STATUS_CANCELLED,
                ]);

                return redirect()->route('vendor.billing.index')
                    ->with('status', 'Tagihan tertunda berhasil dibatalkan. Anda dapat memilih paket langganan kembali.');
            }

            return redirect()->route('vendor.billing.index')
                ->with('error', 'Tidak ada tagihan tertunda yang ditemukan.');
        });
    }
}
