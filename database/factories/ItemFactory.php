<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sampleTitles = [
            'Kostum Raiden Shogun (Genshin Impact)',
            'Kostum Hu Tao Fullset (Genshin Impact)',
            'Kostum Frieren (Sousou no Frieren)',
            'Kostum Yor Forger (Spy x Family)',
            'Kostum Gojo Satoru Jujutsu Kaisen',
            'Kamera Sony A7 Mark III + Lensa 24-70mm',
            'Lensa Sony FE 85mm F1.4 GM',
            'Kebaya Wisuda Modern Brokat Gold',
        ];

        $name = fake()->randomElement($sampleTitles);
        $slug = Str::slug($name).'-'.fake()->unique()->numberBetween(100, 9999);

        return [
            'vendor_id' => Vendor::factory(),
            'name' => $name,
            'slug' => $slug,
            'description' => fake()->paragraph(),
            'size' => fake()->randomElement(['S', 'M', 'L', 'XL', 'Free Size']),
            'included_items' => ['Kostum', 'Wig Styled', 'Aksesoris Kepala', 'Sepatu'],
            'care_instructions' => 'Hanya boleh dry clean. Jangan mencuci wig dengan air panas.',
            'photo_paths' => ['items/sample_1.jpg', 'items/sample_2.jpg'],
            'price_per_day' => fake()->randomElement([75000, 100000, 150000, 200000]),
            'deposit_amount' => fake()->randomElement([100000, 150000, 200000]),
            'fine_per_day' => fake()->randomElement([25000, 50000]),
            'buffer_before' => fake()->randomElement([0, 1]),
            'buffer_after' => fake()->randomElement([1, 2]),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the item is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
