<?php

namespace App\Livewire\Public;

use App\Models\Booking;
use App\Services\QrisService;
use Livewire\Component;

class QrisPaymentCard extends Component
{
    public Booking $booking;

    public array $qrisData = [];

    public bool $isPaid = false;

    public int $totalPayable = 0;

    public function mount(Booking $booking, QrisService $qrisService): void
    {
        $this->booking = $booking;
        $this->isPaid = (bool) $this->booking->is_paid_in_full;
        $this->totalPayable = (int) ($booking->total_price + ($booking->deposit_paid ?? 0));

        if (! $this->isPaid && $this->totalPayable > 0) {
            $this->qrisData = $qrisService->generateDynamicQris(
                amount: $this->totalPayable,
                referenceCode: $booking->reference_code,
                merchantName: $booking->vendor?->name ?? 'JAGASEWA',
                city: 'JAKARTA',
                expiredMinutes: 30
            );
        }
    }

    /**
     * Poll status every 2 seconds.
     */
    public function checkStatus(QrisService $qrisService): void
    {
        if ($this->isPaid) {
            return;
        }

        $this->booking->refresh();
        if ($this->booking->is_paid_in_full) {
            $this->isPaid = true;
        }
    }

    /**
     * Simulate payment for testing / demo.
     */
    public function simulatePayment(QrisService $qrisService): void
    {
        $result = $qrisService->settlePayment(
            referenceCode: $this->booking->reference_code,
            provider: 'simulator'
        );

        if ($result['success'] ?? false) {
            $this->booking->refresh();
            $this->isPaid = true;
        }
    }

    public function render()
    {
        return view('livewire.public.qris-payment-card');
    }
}
