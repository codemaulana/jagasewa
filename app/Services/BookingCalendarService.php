<?php

namespace App\Services;

use App\Models\Item;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class BookingCalendarService
{
    /**
     * Booking statuses that lock the calendar slots.
     *
     * @var list<string>
     */
    public const LOCKING_STATUSES = [
        'pending',
        'approved',
        'active',
        'returned',
    ];

    /**
     * Booking statuses that free the calendar slots.
     *
     * @var list<string>
     */
    public const FREEING_STATUSES = [
        'rejected',
        'cancelled_timeout',
        'cancelled_renter',
        'cancelled_vendor',
        'closed',
    ];

    /**
     * Optional callable resolver to provide bookings for an item in a given range.
     * Useful for mocking or injecting bookings during testing or custom pipelines.
     *
     * @var (callable(Item, Carbon, Carbon): iterable<array|object>)|null
     */
    protected static $bookingsResolver = null;

    /**
     * Set a custom bookings resolver.
     */
    public static function setBookingsResolver(?callable $resolver): void
    {
        static::$bookingsResolver = $resolver;
    }

    /**
     * Determine whether a booking status locks calendar slots.
     */
    public function isStatusLocking(string $status): bool
    {
        if (str_starts_with($status, 'cancelled')) {
            return false;
        }

        return in_array($status, self::LOCKING_STATUSES, true);
    }

    /**
     * Calculate effective date range including buffers.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    public function calculateEffectiveRange(
        CarbonInterface|string $startDate,
        CarbonInterface|string $endDate,
        int $bufferBefore = 0,
        int $bufferAfter = 0
    ): array {
        $start = $this->parseDate($startDate)->subDays(max(0, $bufferBefore));
        $end = $this->parseDate($endDate)->addDays(max(0, $bufferAfter));

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Check if two bookings clash based on Smart Buffer Calendar formula:
     *
     * Rentang efektif A: [A_start - A_before, A_end + A_after]
     * Rentang efektif B: [B_start - B_before, B_end + B_after]
     *
     * Bentrok ⟺ (A_start - A_before) <= (B_end + B_after)
     *           AND (B_start - B_before) <= (A_end + A_after)
     */
    public function hasOverlap(
        CarbonInterface|string $startA,
        CarbonInterface|string $endA,
        int $bufferBeforeA,
        int $bufferAfterA,
        CarbonInterface|string $startB,
        CarbonInterface|string $endB,
        int $bufferBeforeB,
        int $bufferAfterB
    ): bool {
        $parsedStartA = $this->parseDate($startA);
        $parsedEndA = $this->parseDate($endA);
        $parsedStartB = $this->parseDate($startB);
        $parsedEndB = $this->parseDate($endB);

        if ($parsedStartA->gt($parsedEndA)) {
            throw new InvalidArgumentException("Start date A ({$parsedStartA->toDateString()}) cannot be after end date A ({$parsedEndA->toDateString()}).");
        }

        if ($parsedStartB->gt($parsedEndB)) {
            throw new InvalidArgumentException("Start date B ({$parsedStartB->toDateString()}) cannot be after end date B ({$parsedEndB->toDateString()}).");
        }

        $effectiveStartA = (clone $parsedStartA)->subDays(max(0, $bufferBeforeA));
        $effectiveEndA = (clone $parsedEndA)->addDays(max(0, $bufferAfterA));

        $effectiveStartB = (clone $parsedStartB)->subDays(max(0, $bufferBeforeB));
        $effectiveEndB = (clone $parsedEndB)->addDays(max(0, $bufferAfterB));

        return $effectiveStartA->lte($effectiveEndB) && $effectiveStartB->lte($effectiveEndA);
    }

    /**
     * Check if a requested slot is available for an item.
     *
     * @param  iterable<array|object>|null  $existingBookings
     */
    public function isAvailable(
        int|string|Item $itemId,
        CarbonInterface|string $startDate,
        CarbonInterface|string $endDate,
        ?int $bufferBefore = null,
        ?int $bufferAfter = null,
        ?iterable $existingBookings = null
    ): bool {
        $parsedStart = $this->parseDate($startDate);
        $parsedEnd = $this->parseDate($endDate);

        if ($parsedStart->gt($parsedEnd)) {
            throw new InvalidArgumentException("Start date ({$parsedStart->toDateString()}) cannot be after end date ({$parsedEnd->toDateString()}).");
        }

        $item = null;
        $resolvedItemId = $itemId;

        if ($itemId instanceof Item) {
            $item = $itemId;
            $resolvedItemId = $item->id;
        } elseif (is_numeric($itemId) || is_string($itemId)) {
            $item = Item::find($itemId);
        }

        // If item exists in DB and is inactive, it cannot be booked
        if ($item && ! $item->is_active) {
            return false;
        }

        $resolvedBufferBefore = $bufferBefore ?? $item?->buffer_before ?? 0;
        $resolvedBufferAfter = $bufferAfter ?? $item?->buffer_after ?? 0;

        // Fetch existing bookings if not provided
        $bookingsToCheck = $existingBookings;

        if ($bookingsToCheck === null && static::$bookingsResolver !== null && $item) {
            $resolver = static::$bookingsResolver;
            $bookingsToCheck = $resolver($item, $parsedStart, $parsedEnd);
        }

        if ($bookingsToCheck === null && Schema::hasTable('bookings')) {
            $bookingsToCheck = DB::table('bookings')
                ->where('item_id', $resolvedItemId)
                ->whereIn('status', self::LOCKING_STATUSES)
                ->get();
        }

        if ($bookingsToCheck === null) {
            return true;
        }

        foreach ($bookingsToCheck as $booking) {
            $existingStatus = is_array($booking)
                ? ($booking['status'] ?? 'approved')
                : ($booking->status ?? 'approved');

            // Skip cancelled, rejected, or closed bookings
            if (! $this->isStatusLocking($existingStatus)) {
                continue;
            }

            $existingStart = is_array($booking) ? $booking['start_date'] : $booking->start_date;
            $existingEnd = is_array($booking) ? $booking['end_date'] : $booking->end_date;

            $existingBefore = is_array($booking)
                ? ($booking['buffer_before'] ?? $resolvedBufferBefore)
                : ($booking->buffer_before ?? $resolvedBufferBefore);

            $existingAfter = is_array($booking)
                ? ($booking['buffer_after'] ?? $resolvedBufferAfter)
                : ($booking->buffer_after ?? $resolvedBufferAfter);

            if ($this->hasOverlap(
                $existingStart,
                $existingEnd,
                $existingBefore,
                $existingAfter,
                $parsedStart,
                $parsedEnd,
                $resolvedBufferBefore,
                $resolvedBufferAfter
            )) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get availability status for every day in a given month.
     * Returns a JSON-ready array of days with status: 'available' | 'booked' | 'buffer'.
     *
     * @param  string  $yearMonth  Format: 'YYYY-MM'
     * @param  iterable<array|object>|null  $existingBookings
     * @return list<array{date: string, day: int, status: string}>
     */
    public function getMonthAvailability(
        Item|int|string $item,
        string $yearMonth,
        ?iterable $existingBookings = null
    ): array {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $yearMonth)) {
            throw new InvalidArgumentException("Format bulan tidak valid: {$yearMonth}. Gunakan format YYYY-MM.");
        }

        $resolvedItem = $item instanceof Item ? $item : Item::find($item);

        $bufferBefore = $resolvedItem?->buffer_before ?? 0;
        $bufferAfter = $resolvedItem?->buffer_after ?? 0;

        $startOfMonth = Carbon::parse($yearMonth.'-01', 'Asia/Jakarta')->startOfDay();
        $daysInMonth = $startOfMonth->daysInMonth;
        $endOfMonth = (clone $startOfMonth)->endOfMonth()->startOfDay();

        // Effective query boundary to find any booking whose effective range intersects the month
        $queryStart = (clone $startOfMonth)->subDays(max(30, $bufferAfter + 1));
        $queryEnd = (clone $endOfMonth)->addDays(max(30, $bufferBefore + 1));

        $bookings = $existingBookings;

        if ($bookings === null && static::$bookingsResolver !== null && $resolvedItem) {
            $resolver = static::$bookingsResolver;
            $bookings = $resolver($resolvedItem, $queryStart, $queryEnd);
        }

        if ($bookings === null && $resolvedItem && Schema::hasTable('bookings')) {
            $bookings = DB::table('bookings')
                ->where('item_id', $resolvedItem->id)
                ->whereIn('status', self::LOCKING_STATUSES)
                ->where('start_date', '<=', $queryEnd->toDateString())
                ->where('end_date', '>=', $queryStart->toDateString())
                ->get();
        }

        // Map dates to statuses: 'booked' takes precedence over 'buffer'
        $bookedDates = [];
        $bufferDates = [];

        if ($bookings !== null) {
            foreach ($bookings as $booking) {
                $status = is_array($booking)
                    ? ($booking['status'] ?? 'approved')
                    : ($booking->status ?? 'approved');

                if (! $this->isStatusLocking($status)) {
                    continue;
                }

                $rawStart = is_array($booking) ? $booking['start_date'] : $booking->start_date;
                $rawEnd = is_array($booking) ? $booking['end_date'] : $booking->end_date;

                $bookingBufferBefore = is_array($booking)
                    ? ($booking['buffer_before'] ?? $bufferBefore)
                    : ($booking->buffer_before ?? $bufferBefore);

                $bookingBufferAfter = is_array($booking)
                    ? ($booking['buffer_after'] ?? $bufferAfter)
                    : ($booking->buffer_after ?? $bufferAfter);

                $bookingStart = $this->parseDate($rawStart);
                $bookingEnd = $this->parseDate($rawEnd);

                // 1. Mark core rental days as 'booked'
                $current = clone $bookingStart;
                while ($current->lte($bookingEnd)) {
                    $dateStr = $current->toDateString();
                    $bookedDates[$dateStr] = true;
                    $current->addDay();
                }

                // 2. Mark buffer_before days as 'buffer'
                if ($bookingBufferBefore > 0) {
                    $bufStart = (clone $bookingStart)->subDays($bookingBufferBefore);
                    $bufCurrent = clone $bufStart;
                    while ($bufCurrent->lt($bookingStart)) {
                        $dateStr = $bufCurrent->toDateString();
                        $bufferDates[$dateStr] = true;
                        $bufCurrent->addDay();
                    }
                }

                // 3. Mark buffer_after days as 'buffer'
                if ($bookingBufferAfter > 0) {
                    $bufEnd = (clone $bookingEnd)->addDays($bookingBufferAfter);
                    $bufCurrent = (clone $bookingEnd)->addDay();
                    while ($bufCurrent->lte($bufEnd)) {
                        $dateStr = $bufCurrent->toDateString();
                        $bufferDates[$dateStr] = true;
                        $bufCurrent->addDay();
                    }
                }
            }
        }

        // Build result array for every day of the month
        $result = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%s-%02d', $yearMonth, $day);

            if (isset($bookedDates[$date])) {
                $dayStatus = 'booked';
            } elseif (isset($bufferDates[$date])) {
                $dayStatus = 'buffer';
            } else {
                $dayStatus = 'available';
            }

            $result[] = [
                'date' => $date,
                'day' => $day,
                'status' => $dayStatus,
            ];
        }

        return $result;
    }

    /**
     * Parse date into Carbon instance in Asia/Jakarta timezone at start of day.
     */
    protected function parseDate(CarbonInterface|string $date): Carbon
    {
        if ($date instanceof Carbon) {
            return (clone $date)->setTimezone('Asia/Jakarta')->startOfDay();
        }

        if ($date instanceof CarbonInterface) {
            return Carbon::instance($date)->setTimezone('Asia/Jakarta')->startOfDay();
        }

        return Carbon::parse($date, 'Asia/Jakarta')->startOfDay();
    }
}
