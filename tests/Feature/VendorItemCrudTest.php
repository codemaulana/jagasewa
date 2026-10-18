<?php

namespace Tests\Feature;

use App\Filament\Vendor\Resources\Items\Pages\CreateItem;
use App\Filament\Vendor\Resources\Items\Pages\EditItem;
use App\Filament\Vendor\Resources\Items\Pages\ListItems;
use App\Models\Item;
use App\Models\Vendor;
use App\Models\VendorUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VendorItemCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        Storage::fake('s3');
    }

    public function test_unauthenticated_user_cannot_access_vendor_items(): void
    {
        $response = $this->get('/vendor/items');

        $response->assertRedirect('/vendor/login');
    }

    public function test_vendor_can_access_items_index_page(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($user, 'vendor')
            ->withSession(['vendor_id' => $vendor->id])
            ->get('/vendor/items');

        $response->assertStatus(200);
    }

    public function test_vendor_can_see_only_own_items_in_table(): void
    {
        $vendorA = Vendor::factory()->create(['is_active' => true]);
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create(['is_active' => true]);
        $userB = VendorUser::factory()->create(['vendor_id' => $vendorB->id]);

        $itemA = Item::factory()->create([
            'vendor_id' => $vendorA->id,
            'name' => 'Kostum Raiden Shogun',
        ]);

        $itemB = Item::factory()->create([
            'vendor_id' => $vendorB->id,
            'name' => 'Kamera Sony A7IV',
        ]);

        $this->actingAs($userA, 'vendor');
        session(['vendor_id' => $vendorA->id]);

        Livewire::test(ListItems::class)
            ->assertCanSeeTableRecords([$itemA])
            ->assertCanNotSeeTableRecords([$itemB]);
    }

    public function test_vendor_can_render_create_item_page(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($user, 'vendor')
            ->withSession(['vendor_id' => $vendor->id])
            ->get('/vendor/items/create');

        $response->assertStatus(200);
    }

    public function test_vendor_can_create_valid_item_via_livewire(): void
    {
        Storage::fake('s3');

        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        $fakePhoto = UploadedFile::fake()->image('costume.jpg', 600, 600);

        Livewire::test(CreateItem::class)
            ->fillForm([
                'name' => 'Kostum Hutao Genshin Impact',
                'slug' => 'kostum-hutao-genshin-impact',
                'description' => 'Kostum Hutao fullset dengan topi dan cincin.',
                'size' => 'M',
                'included_items' => ['Kostum', 'Topi', 'Wig', 'Aksesoris'],
                'care_instructions' => 'Dry clean only, jangan disikat.',
                'price_per_day' => 150000,
                'deposit_amount' => 50000,
                'fine_per_day' => 30000,
                'buffer_before' => 1,
                'buffer_after' => 2,
                'photo_paths' => [$fakePhoto],
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('items', [
            'vendor_id' => $vendor->id,
            'name' => 'Kostum Hutao Genshin Impact',
            'slug' => 'kostum-hutao-genshin-impact',
            'size' => 'M',
            'price_per_day' => 150000,
            'deposit_amount' => 50000,
            'fine_per_day' => 30000,
            'buffer_before' => 1,
            'buffer_after' => 2,
            'is_active' => true,
        ]);
    }

    public function test_creating_item_with_zero_or_negative_price_fails_validation(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(CreateItem::class)
            ->fillForm([
                'name' => 'Kostum Gratis',
                'slug' => 'kostum-gratis',
                'price_per_day' => 0,
            ])
            ->call('create')
            ->assertHasFormErrors(['price_per_day']);

        Livewire::test(CreateItem::class)
            ->fillForm([
                'name' => 'Kostum Minus',
                'slug' => 'kostum-minus',
                'price_per_day' => -10000,
            ])
            ->call('create')
            ->assertHasFormErrors(['price_per_day']);
    }

    public function test_creating_item_with_negative_deposit_or_buffer_fails_validation(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(CreateItem::class)
            ->fillForm([
                'name' => 'Kostum Test',
                'slug' => 'kostum-test',
                'price_per_day' => 100000,
                'deposit_amount' => -5000,
                'fine_per_day' => -1000,
                'buffer_before' => -1,
                'buffer_after' => -2,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'deposit_amount',
                'fine_per_day',
                'buffer_before',
                'buffer_after',
            ]);
    }

    public function test_vendor_can_update_own_item(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Kostum Awal',
            'size' => 'M',
            'price_per_day' => 100000,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(EditItem::class, ['record' => $item->getRouteKey()])
            ->fillForm([
                'name' => 'Kostum Diperbarui',
                'price_per_day' => 135000,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Kostum Diperbarui',
            'price_per_day' => 135000,
        ]);
    }

    public function test_vendor_cannot_access_other_vendor_item_returns_404(): void
    {
        $vendorA = Vendor::factory()->create(['is_active' => true]);
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create(['is_active' => true]);
        $itemB = Item::factory()->create([
            'vendor_id' => $vendorB->id,
            'name' => 'Barang Vendor B',
        ]);

        // Accessing other vendor's item edit page returns 404
        $response = $this->actingAs($userA, 'vendor')
            ->withSession(['vendor_id' => $vendorA->id])
            ->get("/vendor/items/{$itemB->getRouteKey()}/edit");

        $response->assertStatus(404);

        // Accessing other vendor's item view page returns 404
        $viewResponse = $this->actingAs($userA, 'vendor')
            ->withSession(['vendor_id' => $vendorA->id])
            ->get("/vendor/items/{$itemB->getRouteKey()}");

        $viewResponse->assertStatus(404);
    }

    public function test_slug_must_be_unique_per_vendor_but_allowed_across_different_vendors(): void
    {
        $vendorA = Vendor::factory()->create(['is_active' => true]);
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create(['is_active' => true]);
        $userB = VendorUser::factory()->create(['vendor_id' => $vendorB->id]);

        // Vendor A creates item with slug 'genshin-furina'
        Item::factory()->create([
            'vendor_id' => $vendorA->id,
            'name' => 'Furina Cosplay',
            'slug' => 'genshin-furina',
        ]);

        // Vendor A trying to create another item with same slug fails
        $this->actingAs($userA, 'vendor');
        session(['vendor_id' => $vendorA->id]);

        Livewire::test(CreateItem::class)
            ->fillForm([
                'name' => 'Furina Duplicate',
                'slug' => 'genshin-furina',
                'price_per_day' => 120000,
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);

        // Vendor B CAN create an item with same slug 'genshin-furina'
        $this->actingAs($userB, 'vendor');
        session(['vendor_id' => $vendorB->id]);

        Livewire::test(CreateItem::class)
            ->fillForm([
                'name' => 'Furina Milik Vendor B',
                'slug' => 'genshin-furina',
                'price_per_day' => 130000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('items', [
            'vendor_id' => $vendorB->id,
            'slug' => 'genshin-furina',
        ]);
    }
}
