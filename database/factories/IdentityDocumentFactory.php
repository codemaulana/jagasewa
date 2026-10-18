<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\IdentityDocument;
use App\Models\Renter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IdentityDocument>
 */
class IdentityDocumentFactory extends Factory
{
    protected $model = IdentityDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'renter_id' => function (array $attributes) {
                if (isset($attributes['booking_id']) && is_numeric($attributes['booking_id'])) {
                    $booking = Booking::find($attributes['booking_id']);
                    if ($booking && $booking->renter_id) {
                        return $booking->renter_id;
                    }
                }

                return Renter::factory();
            },
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'storage_path' => 'identity-documents/'.(string) Str::uuid().'.jpg',
            'storage_disk' => 's3',
            'file_size' => fake()->numberBetween(150000, 2500000),
            'mime_type' => 'image/jpeg',
            'consent_given' => true,
            'consent_version' => 'v1.0',
            'consent_at' => Carbon::now('Asia/Jakarta'),
            'delete_scheduled_at' => Carbon::now('Asia/Jakarta')->addDays(30),
        ];
    }

    /**
     * Indicate that the document is a KTP.
     */
    public function ktp(): static
    {
        return $this->state(fn (array $attributes) => [
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
        ]);
    }

    /**
     * Indicate that the document is a selfie.
     */
    public function selfie(): static
    {
        return $this->state(fn (array $attributes) => [
            'doc_type' => IdentityDocument::DOC_TYPE_SELFIE,
        ]);
    }

    /**
     * Indicate that the document is scheduled for immediate deletion.
     */
    public function readyForDeletion(): static
    {
        return $this->state(fn (array $attributes) => [
            'delete_scheduled_at' => Carbon::now('Asia/Jakarta')->subDay(),
        ]);
    }

    /**
     * Indicate that the document is soft-deleted.
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => Carbon::now('Asia/Jakarta'),
        ]);
    }
}
