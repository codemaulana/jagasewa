<?php

namespace App\Livewire;

use App\Models\Item;
use App\Services\BookingCalendarService;
use Carbon\Carbon;
use Livewire\Component;

class ItemAvailabilityCalendar extends Component
{
    public Item $item;

    public string $currentMonth;

    public ?string $selectedStartDate = null;

    public ?string $selectedEndDate = null;

    public ?string $errorMessage = null;

    protected static array $monthNamesIndonesian = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function mount(Item $item): void
    {
        $this->item = $item;
        $this->currentMonth = Carbon::now('Asia/Jakarta')->format('Y-m');
    }

    public function goToPreviousMonth(): void
    {
        $target = Carbon::parse($this->currentMonth.'-01', 'Asia/Jakarta')->subMonth();
        $minAllowedMonth = Carbon::now('Asia/Jakarta')->startOfMonth();

        if ($target->gte($minAllowedMonth)) {
            $this->currentMonth = $target->format('Y-m');
            $this->errorMessage = null;
        }
    }

    public function goToNextMonth(): void
    {
        $this->currentMonth = Carbon::parse($this->currentMonth.'-01', 'Asia/Jakarta')->addMonth()->format('Y-m');
        $this->errorMessage = null;
    }

    public function selectDate(string $date, BookingCalendarService $calendarService): void
    {
        $this->errorMessage = null;

        // Fetch availability map for the month of the clicked date
        $monthOfDate = substr($date, 0, 7);
        $monthDays = $calendarService->getMonthAvailability($this->item, $monthOfDate);
        $statusByDate = collect($monthDays)->pluck('status', 'date')->all();

        $clickedStatus = $statusByDate[$date] ?? 'available';

        // Cannot select booked or buffer date
        if ($clickedStatus === 'booked') {
            $this->errorMessage = 'Tanggal tersebut sudah terpesan dan tidak dapat dipilih.';

            return;
        }

        if ($clickedStatus === 'buffer') {
            $this->errorMessage = 'Tanggal tersebut merupakan masa jeda (buffer) persiapan atau laundry vendor.';

            return;
        }

        // Selection logic:
        // Case 1: No start date chosen yet, or already have full range -> start new range
        if ($this->selectedStartDate === null || ($this->selectedStartDate !== null && $this->selectedEndDate !== null)) {
            $this->selectedStartDate = $date;
            $this->selectedEndDate = null;

            return;
        }

        // Case 2: Start date chosen, user clicks an earlier date -> reset start date to earlier date
        if ($date < $this->selectedStartDate) {
            $this->selectedStartDate = $date;
            $this->selectedEndDate = null;

            return;
        }

        // Case 3: Start date chosen, user clicks on or after start date -> set end date
        // Validate that no date within the selected range is booked or buffer
        $start = Carbon::parse($this->selectedStartDate, 'Asia/Jakarta');
        $end = Carbon::parse($date, 'Asia/Jakarta');

        $cursor = clone $start;
        $rangeHasConflict = false;

        while ($cursor->lte($end)) {
            $cursorDateStr = $cursor->toDateString();
            $cursorMonthStr = substr($cursorDateStr, 0, 7);

            $checkDays = $calendarService->getMonthAvailability($this->item, $cursorMonthStr);
            $checkMap = collect($checkDays)->pluck('status', 'date')->all();

            $status = $checkMap[$cursorDateStr] ?? 'available';
            if ($status !== 'available') {
                $rangeHasConflict = true;
                break;
            }

            $cursor->addDay();
        }

        if ($rangeHasConflict) {
            $this->errorMessage = 'Rentang sewa tidak boleh melewati hari yang sudah terpesan atau dalam masa jeda buffer.';
            $this->selectedEndDate = null;

            return;
        }

        $this->selectedEndDate = $date;

        $totalDays = $this->getTotalDays();
        $totalPrice = $totalDays * $this->item->price_per_day;

        $this->dispatch('dates-selected', [
            'startDate' => $this->selectedStartDate,
            'endDate' => $this->selectedEndDate,
            'totalDays' => $totalDays,
            'totalPrice' => $totalPrice,
        ]);
    }

    public function resetSelection(): void
    {
        $this->selectedStartDate = null;
        $this->selectedEndDate = null;
        $this->errorMessage = null;

        $this->dispatch('dates-cleared');
    }

    public function getTotalDays(): int
    {
        if (! $this->selectedStartDate) {
            return 0;
        }

        if (! $this->selectedEndDate) {
            return 1;
        }

        $start = Carbon::parse($this->selectedStartDate, 'Asia/Jakarta');
        $end = Carbon::parse($this->selectedEndDate, 'Asia/Jakarta');

        return $start->diffInDays($end) + 1;
    }

    public function getTotalPrice(): int
    {
        return $this->getTotalDays() * (int) $this->item->price_per_day;
    }

    public function formatIndonesianDate(?string $date): string
    {
        if (! $date) {
            return '';
        }

        $parsed = Carbon::parse($date, 'Asia/Jakarta');
        $monthName = self::$monthNamesIndonesian[(int) $parsed->format('n')] ?? $parsed->format('M');
        $shortMonth = substr($monthName, 0, 3);

        return $parsed->format('d').' '.$shortMonth.' '.$parsed->format('Y');
    }

    public function getFormattedMonthLabel(): string
    {
        $parsed = Carbon::parse($this->currentMonth.'-01', 'Asia/Jakarta');
        $monthName = self::$monthNamesIndonesian[(int) $parsed->format('n')] ?? $parsed->format('F');

        return $monthName.' '.$parsed->format('Y');
    }

    public function canGoPreviousMonth(): bool
    {
        $target = Carbon::parse($this->currentMonth.'-01', 'Asia/Jakarta')->subMonth();
        $minAllowedMonth = Carbon::now('Asia/Jakarta')->startOfMonth();

        return $target->gte($minAllowedMonth);
    }

    public function render(BookingCalendarService $calendarService)
    {
        $days = $calendarService->getMonthAvailability($this->item, $this->currentMonth);

        $startOfMonth = Carbon::parse($this->currentMonth.'-01', 'Asia/Jakarta')->startOfMonth();

        // dayOfWeekIso: Monday is 1, Sunday is 7
        // Empty cells needed before day 1: (dayOfWeekIso - 1)
        $leadingEmptyDays = $startOfMonth->dayOfWeekIso - 1;

        return view('livewire.item-availability-calendar', [
            'days' => $days,
            'leadingEmptyDays' => $leadingEmptyDays,
            'monthLabel' => $this->getFormattedMonthLabel(),
            'canGoPrevious' => $this->canGoPreviousMonth(),
            'totalDays' => $this->getTotalDays(),
            'totalPrice' => $this->getTotalPrice(),
        ]);
    }
}
