<?php

namespace Database\Factories;

use App\Models\Renter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Renter>
 */
class RenterFactory extends Factory
{
    protected $model = Renter::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $phone = '0812'.fake()->numerify('########');
        $nik = '9999'.fake()->numerify('############'); // 16-digit test NIK starting with 9999

        return [
            'name' => fake()->name(),
            'phone' => $phone,
            'nik' => $nik,
            'instagram_handle_hash' => Renter::hashValue(fake()->userName()),
            'address' => fake()->address(),
        ];
    }
}
