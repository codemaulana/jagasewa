<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AutoCancelPendingBookingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job to automatically cancel expired pending bookings.
     */
    public function handle(): int
    {
        $now = Carbon::now('Asia/Jakarta');

        $expiredBookings = Booking::where('status', Booking::STATUS_PENDING)
            ->whereNotNull('pending_expires_at')
            ->where('pending_expires_at', '<=', $now)
            ->get();

        $cancelledCount = 0;

        foreach ($expiredBookings as $booking) {
            DB::transaction(function () use ($booking, &$cancelledCount) {
                // Pessimistic lock on the individual booking row
                $lockedBooking = Booking::where('id', $booking->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedBooking || $lockedBooking->status !== Booking::STATUS_PENDING) {
                    return;
                }

                $lockedBooking->status = Booking::STATUS_CANCELLED_TIMEOUT;
                $lockedBooking->lock_version = ($lockedBooking->lock_version ?? 0) + 1;
                $lockedBooking->save();

                // Reschedule identity documents retention to cancelled retention period (BR-05)
                $lockedBooking->identityDocuments()->update([
                    'delete_scheduled_at' => Carbon::now('Asia/Jakarta')->addDays(config('jagasewa.retention.cancelled_days', 3)),
                ]);

                AuditService::log(
                    'booking_cancelled_timeout',
                    'booking',
                    (string) $lockedBooking->id,
                    [
                        'reference_code' => $lockedBooking->reference_code,
                        'reason' => 'auto_cancel_timeout_24h',
                        'expired_at' => $lockedBooking->pending_expires_at?->toIso8601String(),
                    ],
                    $lockedBooking->vendor_id
                );

                $cancelledCount++;
            });
        }

        return $cancelledCount;
    }
}
