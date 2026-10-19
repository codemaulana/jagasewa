<?php

namespace Database\Factories;

use App\Models\DamageItem;
use App\Models\Settlement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DamageItem>
 */
class DamageItemFactory extends Factory
{
    protected $model = DamageItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'settlement_id' => Settlement::factory(),
            'description' => fake()->randomElement([
                'Kancing baju terlepas',
                'Noda makeup pada kerah kostum',
                'Tali selempang robek sebagian',
                'Goresan kecil pada lensa kamera',
                'Resleting macet atau rusak',
            ]),
            'repair_cost' => fake()->randomElement([25000, 50000, 75000, 100000]),
            'photo_path' => null,
        ];
    }
}
