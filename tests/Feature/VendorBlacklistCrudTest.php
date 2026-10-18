<?php

namespace Tests\Feature;

use App\Filament\Vendor\Resources\BlacklistEntries\Pages\CreateBlacklistEntry;
use App\Filament\Vendor\Resources\BlacklistEntries\Pages\ListBlacklistEntries;
use App\Models\BlacklistEntry;
use App\Models\Vendor;
use App\Models\VendorUser;
use App\Services\IdentityHashService;
use Filament\Actions\Exceptions\ActionNotResolvableException;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorBlacklistCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
    }

    public function test_unauthenticated_user_cannot_access_blacklist_pages(): void
    {
        $this->get('/vendor/blacklist-entries')->assertRedirect('/vendor/login');
        $this->get('/vendor/blacklist-entries/create')->assertRedirect('/vendor/login');
    }

    public function test_authenticated_vendor_can_access_blacklist_index_page(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($user, 'vendor')
            ->withSession(['vendor_id' => $vendor->id])
            ->get('/vendor/blacklist-entries');

        $response->assertStatus(200);
    }

    public function test_vendor_can_see_only_own_blacklist_entries_in_table(): void
    {
        $vendorA = Vendor::factory()->create(['is_active' => true]);
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create(['is_active' => true]);

        $entryA = BlacklistEntry::factory()->create([
            'vendor_id' => $vendorA->id,
            'reason' => 'Penyewa A merusak kostum cosplay',
        ]);

        $entryB = BlacklistEntry::factory()->create([
            'vendor_id' => $vendorB->id,
            'reason' => 'Penyewa B kabur membawa kebaya',
        ]);

        $this->actingAs($userA, 'vendor');
        session(['vendor_id' => $vendorA->id]);

        Livewire::test(ListBlacklistEntries::class)
            ->assertCanSeeTableRecords([$entryA])
            ->assertCanNotSeeTableRecords([$entryB]);
    }

    public function test_vendor_can_create_blacklist_entry_for_whatsapp(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        $rawPhone = '0812-3456-7890';
        $expectedHash = IdentityHashService::hashIdentity(BlacklistEntry::TYPE_WA, $rawPhone);

        Livewire::test(CreateBlacklistEntry::class)
            ->fillForm([
                'identity_type' => BlacklistEntry::TYPE_WA,
                'identity_value' => $rawPhone,
                'reason' => 'Merusak barang sewa dan memblokir kontak WA.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Verify stored in DB with hash and without plaintext
        $this->assertDatabaseHas('blacklist_entries', [
            'vendor_id' => $vendor->id,
            'identity_type' => BlacklistEntry::TYPE_WA,
            'identity_hash' => $expectedHash,
            'scope' => BlacklistEntry::SCOPE_PRIVATE,
            'reason' => 'Merusak barang sewa dan memblokir kontak WA.',
        ]);

        // Verify plaintext is not stored in DB
        $entry = BlacklistEntry::where('vendor_id', $vendor->id)->first();
        $this->assertNotNull($entry);
        $this->assertStringNotContainsString($rawPhone, json_encode($entry->toArray()));

        // Verify audit log recorded
        $this->assertDatabaseHas('audit_logs', [
            'vendor_id' => $vendor->id,
            'event' => 'blacklist_created',
            'entity_type' => 'blacklist_entry',
            'entity_id' => (string) $entry->id,
        ]);
    }

    public function test_vendor_can_create_blacklist_entry_for_nik_and_instagram(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        $rawNik = '3271 1234 5678 9012';
        $expectedNikHash = IdentityHashService::hashIdentity(BlacklistEntry::TYPE_NIK, $rawNik);

        Livewire::test(CreateBlacklistEntry::class)
            ->fillForm([
                'identity_type' => BlacklistEntry::TYPE_NIK,
                'identity_value' => $rawNik,
                'reason' => 'Identitas NIK palsu dan tidak mengembalikan barang.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('blacklist_entries', [
            'vendor_id' => $vendor->id,
            'identity_type' => BlacklistEntry::TYPE_NIK,
            'identity_hash' => $expectedNikHash,
        ]);

        $rawIg = '@BudiRental_Cosplay';
        $expectedIgHash = IdentityHashService::hashIdentity(BlacklistEntry::TYPE_INSTAGRAM, $rawIg);

        Livewire::test(CreateBlacklistEntry::class)
            ->fillForm([
                'identity_type' => BlacklistEntry::TYPE_INSTAGRAM,
                'identity_value' => $rawIg,
                'reason' => 'Akun IG sering melakukan rental fiktif.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('blacklist_entries', [
            'vendor_id' => $vendor->id,
            'identity_type' => BlacklistEntry::TYPE_INSTAGRAM,
            'identity_hash' => $expectedIgHash,
        ]);
    }

    public function test_creating_duplicate_blacklist_entry_fails_validation(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        // Pre-create entry with phone 081234567890
        BlacklistEntry::factory()->create([
            'vendor_id' => $vendor->id,
            'identity_type' => BlacklistEntry::TYPE_WA,
            'identity_hash' => IdentityHashService::hashIdentity(BlacklistEntry::TYPE_WA, '081234567890'),
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        // Attempt to create entry with different variation of the exact same number
        Livewire::test(CreateBlacklistEntry::class)
            ->fillForm([
                'identity_type' => BlacklistEntry::TYPE_WA,
                'identity_value' => '+62 812-3456-7890',
                'reason' => 'Duplikasi nomor telepon.',
            ])
            ->call('create')
            ->assertHasFormErrors(['identity_value']);
    }

    public function test_vendor_can_delete_own_blacklist_entry(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $entry = BlacklistEntry::factory()->create([
            'vendor_id' => $vendor->id,
            'reason' => 'Entri sementara untuk dihapus',
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(ListBlacklistEntries::class)
            ->callTableAction('delete', $entry);

        $this->assertDatabaseMissing('blacklist_entries', [
            'id' => $entry->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'vendor_id' => $vendor->id,
            'event' => 'blacklist_deleted',
            'entity_type' => 'blacklist_entry',
            'entity_id' => (string) $entry->id,
        ]);
    }

    public function test_vendor_cannot_delete_other_vendor_blacklist_entry(): void
    {
        $vendorA = Vendor::factory()->create(['is_active' => true]);
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create(['is_active' => true]);
        $entryB = BlacklistEntry::factory()->create([
            'vendor_id' => $vendorB->id,
            'reason' => 'Entri milik vendor B',
        ]);

        $this->actingAs($userA, 'vendor');
        session(['vendor_id' => $vendorA->id]);

        $this->expectException(ActionNotResolvableException::class);

        Livewire::test(ListBlacklistEntries::class)
            ->callTableAction('delete', $entryB);
    }
}
