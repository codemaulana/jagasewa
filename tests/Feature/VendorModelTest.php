<?php

namespace Tests\Feature;

use App\Models\Vendor;
use App\Models\VendorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendorModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_vendor_with_factory(): void
    {
        $vendor = Vendor::factory()->create([
            'wa_number' => '+6281234567890',
        ]);

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'slug' => $vendor->slug,
        ]);

        $this->assertNotEmpty($vendor->ulid);
        $this->assertEquals(26, strlen($vendor->ulid));
        $this->assertEquals('+6281234567890', $vendor->wa_number);

        // Verify that raw DB value for wa_number is encrypted (not plaintext)
        $rawWa = DB::table('vendors')->where('id', $vendor->id)->value('wa_number');
        $this->assertNotEquals('+6281234567890', $rawWa);
    }

    public function test_can_create_vendor_user_with_factory_and_relations(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create([
            'vendor_id' => $vendor->id,
            'role' => 'owner',
        ]);

        $this->assertTrue($user->isOwner());
        $this->assertEquals($vendor->id, $user->vendor->id);
        $this->assertTrue($vendor->users->contains($user));
    }

    public function test_deleting_vendor_cascades_to_vendor_users(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $this->assertDatabaseHas('vendor_users', ['id' => $user->id]);

        $vendor->delete();

        $this->assertDatabaseMissing('vendor_users', ['id' => $user->id]);
        $this->assertDatabaseMissing('vendors', ['id' => $vendor->id]);
    }

    public function test_migrations_can_be_rolled_back_cleanly(): void
    {
        // Test rollback of the two new migrations
        $exitCode = Artisan::call('migrate:rollback', ['--step' => 2]);
        $this->assertSame(0, $exitCode);

        // Re-run migration to restore database state for further tests
        $remigrateCode = Artisan::call('migrate');
        $this->assertSame(0, $remigrateCode);
    }
}
