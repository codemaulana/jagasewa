<?php

namespace App\Livewire\Public;

use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Services\AuditService;
use App\Services\BookingCalendarService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

class BookingForm extends Component
{
    use WithFileUploads;

    public Item $item;

    public string $name = '';

    public string $phone = '';

    public string $address = '';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public string $renterNotes = '';

    public bool $agreePrivacy = false;

    public string $idempotencyKey = '';

    public $ktpPhoto;

    public $selfiePhoto;

    public ?string $errorMessage = null;

    public int $totalDays = 0;

    public int $totalPrice = 0;

    public int $depositAmount = 0;

    protected array $rules = [
        'name' => 'required|string|min:3|max:100',
        'phone' => ['required', 'string', 'min:9', 'max:20', 'regex:/^(\+62|62|08)[0-9]{8,15}$/'],
        'address' => 'required|string|min:5|max:500',
        'startDate' => 'required|date_format:Y-m-d|after_or_equal:today',
        'endDate' => 'required|date_format:Y-m-d|after_or_equal:startDate',
        'renterNotes' => 'nullable|string|max:500',
        'agreePrivacy' => 'accepted',
        'idempotencyKey' => 'required|string',
        'ktpPhoto' => 'nullable|image|max:5120',
        'selfiePhoto' => 'nullable|image|max:5120',
    ];

    protected array $messages = [
        'name.required' => 'Nama lengkap wajib diisi.',
        'name.min' => 'Nama lengkap minimal 3 karakter.',
        'phone.required' => 'Nomor WhatsApp wajib diisi.',
        'phone.regex' => 'Format nomor WhatsApp tidak valid (gunakan format 08xx atau +62xx).',
        'address.required' => 'Alamat pengiriman wajib diisi.',
        'startDate.required' => 'Pilih tanggal mulai sewa.',
        'startDate.after_or_equal' => 'Tanggal mulai tidak boleh di masa lampau.',
        'endDate.required' => 'Pilih tanggal selesai sewa.',
        'endDate.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        'agreePrivacy.accepted' => 'Anda wajib menyetujui kebijakan privasi dan penggunaan data verifikasi.',
    ];

    public function mount(Item $item): void
    {
        $this->item = $item;
        $this->depositAmount = (int) $item->deposit_amount;
        $this->idempotencyKey = (string) Str::uuid();
    }

    #[On('dates-selected')]
    public function onDatesSelected(array $data): void
    {
        $this->startDate = $data['startDate'] ?? $this->startDate;
        $this->endDate = $data['endDate'] ?? $this->endDate;
        $this->recalculate();
    }

    #[On('dates-cleared')]
    public function onDatesCleared(): void
    {
        $this->startDate = null;
        $this->endDate = null;
        $this->recalculate();
    }

    public function updatedStartDate(): void
    {
        $this->recalculate();
    }

    public function updatedEndDate(): void
    {
        $this->recalculate();
    }

    public function recalculate(): void
    {
        $this->errorMessage = null;

        if (! empty($this->startDate) && ! empty($this->endDate) && $this->startDate <= $this->endDate) {
            $start = Carbon::parse($this->startDate, 'Asia/Jakarta');
            $end = Carbon::parse($this->endDate, 'Asia/Jakarta');
            $this->totalDays = $start->diffInDays($end) + 1;
            $this->totalPrice = $this->totalDays * (int) $this->item->price_per_day;
        } else {
            $this->totalDays = 0;
            $this->totalPrice = 0;
        }
    }

    public function submit(BookingCalendarService $calendarService)
    {
        $this->errorMessage = null;
        $this->validate();

        // 1. Idempotency Check: if booking with idempotencyKey exists, redirect to success
        $existing = Booking::where('idempotency_key', $this->idempotencyKey)->first();
        if ($existing) {
            return redirect()->route('public.booking.success', ['ulid' => $existing->ulid]);
        }

        try {
            $booking = DB::transaction(function () use ($calendarService) {
                // 2. Pessimistic Locking: Lock current booking rows for this item to prevent concurrent double-booking
                Booking::where('item_id', $this->item->id)
                    ->whereIn('status', BookingCalendarService::LOCKING_STATUSES)
                    ->lockForUpdate()
                    ->get();

                // 3. Availability verification
                if (! $calendarService->isAvailable($this->item, $this->startDate, $this->endDate)) {
                    throw new RuntimeException('Slot tanggal sewa yang Anda pilih bertabrakan dengan pesanan lain atau jeda persiapan barang.');
                }

                // 4. Find or create Renter with encrypted PII and blind index
                $normalizedPhone = Renter::normalizePhone($this->phone);
                $phoneHash = Renter::hashValue($normalizedPhone);

                /** @var Renter|null $renter */
                $renter = Renter::where('phone_hash', $phoneHash)->first();
                if (! $renter) {
                    $renter = Renter::create([
                        'name' => $this->name,
                        'phone' => $this->phone,
                        'address' => $this->address,
                    ]);
                } else {
                    $renter->update([
                        'name' => $this->name,
                        'address' => $this->address,
                    ]);
                }

                // 5. Calculate final totals
                $start = Carbon::parse($this->startDate, 'Asia/Jakarta');
                $end = Carbon::parse($this->endDate, 'Asia/Jakarta');
                $totalDays = $start->diffInDays($end) + 1;
                $totalPrice = $totalDays * (int) $this->item->price_per_day;
                $depositPaid = (int) $this->item->deposit_amount;

                // 6. Create Booking record
                $newBooking = Booking::create([
                    'vendor_id' => $this->item->vendor_id,
                    'item_id' => $this->item->id,
                    'renter_id' => $renter->id,
                    'start_date' => $this->startDate,
                    'end_date' => $this->endDate,
                    'total_days' => $totalDays,
                    'total_price' => $totalPrice,
                    'deposit_paid' => $depositPaid,
                    'status' => Booking::STATUS_PENDING,
                    'idempotency_key' => $this->idempotencyKey,
                    'renter_notes' => $this->renterNotes,
                    'lock_version' => 1,
                ]);

                // 7. Audit log (without PII)
                AuditService::log(
                    'booking_created',
                    'booking',
                    (string) $newBooking->id,
                    [
                        'reference_code' => $newBooking->reference_code,
                        'total_price' => $totalPrice,
                        'start_date' => $this->startDate,
                        'end_date' => $this->endDate,
                    ],
                    $this->item->vendor_id
                );

                return $newBooking;
            });

            return redirect()->route('public.booking.success', ['ulid' => $booking->ulid]);
        } catch (RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.public.booking-form');
    }
}
