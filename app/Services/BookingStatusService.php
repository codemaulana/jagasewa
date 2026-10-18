<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class BookingStatusService
{
    /**
     * Approve a pending booking (BR-02).
     */
    public function approve(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $lockedBooking = Booking::where('id', $booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBooking->status !== Booking::STATUS_PENDING) {
                throw new DomainException("Booking dengan status '{$lockedBooking->status}' tidak dapat disetujui.");
            }

            $now = Carbon::now('Asia/Jakarta');
            $lockedBooking->status = Booking::STATUS_APPROVED;
            $lockedBooking->approved_at = $now;
            $lockedBooking->lock_version = ($lockedBooking->lock_version ?? 0) + 1;
            $lockedBooking->save();

            AuditService::log(
                'booking_approved',
                'booking',
                (string) $lockedBooking->id,
                [
                    'reference_code' => $lockedBooking->reference_code,
                    'approved_at' => $now->toIso8601String(),
                ],
                $lockedBooking->vendor_id
            );

            return $lockedBooking;
        });
    }

    /**
     * Reject a pending booking (BR-02).
     */
    public function reject(Booking $booking, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $reason) {
            $lockedBooking = Booking::where('id', $booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBooking->status !== Booking::STATUS_PENDING) {
                throw new DomainException("Booking dengan status '{$lockedBooking->status}' tidak dapat ditolak.");
            }

            $lockedBooking->status = Booking::STATUS_REJECTED;
            if (! empty($reason)) {
                $lockedBooking->vendor_notes = $reason;
            }
            $lockedBooking->lock_version = ($lockedBooking->lock_version ?? 0) + 1;
            $lockedBooking->save();

            // Reschedule identity documents retention to cancelled retention period (BR-05)
            $cancelledDays = (int) config('jagasewa.retention.cancelled_days', 3);
            $lockedBooking->identityDocuments()->update([
                'delete_scheduled_at' => Carbon::now('Asia/Jakarta')->addDays($cancelledDays),
            ]);

            AuditService::log(
                'booking_rejected',
                'booking',
                (string) $lockedBooking->id,
                [
                    'reference_code' => $lockedBooking->reference_code,
                    'reason' => $reason,
                ],
                $lockedBooking->vendor_id
            );

            return $lockedBooking;
        });
    }
}
