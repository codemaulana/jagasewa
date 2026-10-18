<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Vendor;
use App\Models\VendorUser;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ItemModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_item_with_factory(): void
    {
        $vendor = Vendor::factory()->create();

        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Kostum Raiden Shogun Genshin Impact',
            'price_per_day' => 150000,
            'deposit_amount' => 200000,
            'fine_per_day' => 50000,
            'buffer_before' => 1,
            'buffer_after' => 2,
            'size' => 'M',
            'included_items' => ['Kostum', 'Wig Styled', 'Obi', 'Aksesoris'],
            'care_instructions' => 'Dry clean only. Wig disisir dari bawah.',
        ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'vendor_id' => $vendor->id,
            'name' => 'Kostum Raiden Shogun Genshin Impact',
            'price_per_day' => 150000,
            'deposit_amount' => 200000,
            'fine_per_day' => 50000,
            'size' => 'M',
            'buffer_before' => 1,
            'buffer_after' => 2,
        ]);

        // Assert ULID generated
        $this->assertNotNull($item->ulid);
        $this->assertEquals(26, strlen($item->ulid));

        // Assert integer casting
        $this->assertIsInt($item->price_per_day);
        $this->assertIsInt($item->deposit_amount);
        $this->assertIsInt($item->deposit); // Deposit accessor alias
        $this->assertIsInt($item->fine_per_day);
        $this->assertIsInt($item->buffer_before);
        $this->assertIsInt($item->buffer_after);

        // Assert array casting
        $this->assertIsArray($item->included_items);
        $this->assertContains('Kostum', $item->included_items);
        $this->assertContains('Wig Styled', $item->included_items);
    }

    public function test_item_belongs_to_vendor_relation(): void
    {
        $vendor = Vendor::factory()->create();
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);

        $this->assertInstanceOf(Vendor::class, $item->vendor);
        $this->assertEquals($vendor->id, $item->vendor->id);

        $this->assertTrue($vendor->items->contains($item));
    }

    public function test_deleting_vendor_cascades_to_items(): void
    {
        $vendor = Vendor::factory()->create();
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);

        $this->assertDatabaseHas('items', ['id' => $item->id]);

        $vendor->delete();

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    public function test_belongs_to_vendor_trait_auto_assigns_vendor_id(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $this->actingAs($user, 'vendor');

        $item = Item::create([
            'name' => 'Lensa Kamera Sony FE 85mm',
            'slug' => 'lensa-sony-85mm',
            'price_per_day' => 100000,
            'deposit_amount' => 300000,
            'fine_per_day' => 50000,
        ]);

        $this->assertEquals($vendor->id, $item->vendor_id);
    }

    public function test_slug_unique_per_vendor(): void
    {
        $vendorA = Vendor::factory()->create();
        $vendorB = Vendor::factory()->create();

        // Vendor A creates item with slug 'frieren-fullset'
        Item::factory()->create([
            'vendor_id' => $vendorA->id,
            'slug' => 'frieren-fullset',
        ]);

        // Vendor B creates item with same slug -> should succeed (allowed across different vendors)
        $itemB = Item::factory()->create([
            'vendor_id' => $vendorB->id,
            'slug' => 'frieren-fullset',
        ]);
        $this->assertDatabaseHas('items', ['id' => $itemB->id]);

        // Vendor A creates duplicate slug -> should fail
        $this->expectException(QueryException::class);
        Item::factory()->create([
            'vendor_id' => $vendorA->id,
            'slug' => 'frieren-fullset',
        ]);
    }

    public function test_items_migration_can_be_rolled_back_cleanly(): void
    {
        $this->assertTrue(Schema::hasTable('items'));

        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_10_18_000004_create_items_table.php',
        ])->assertSuccessful();

        $this->assertFalse(Schema::hasTable('items'));

        // Re-run migration for subsequent tests
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_10_18_000004_create_items_table.php',
        ])->assertSuccessful();
    }
}
