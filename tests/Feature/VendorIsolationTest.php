<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Concerns\BelongsToVendor;
use App\Models\IdentityDocument;
use App\Models\Vendor;
use App\Models\VendorUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Dummy model using BelongsToVendor trait for testing
class TestResource extends Model
{
    use BelongsToVendor;

    protected $table = 'test_resources';

    protected $fillable = ['vendor_id', 'title'];
}

class VendorIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('title');
            $table->timestamps();
        });

        Route::middleware(['web', 'vendor.ownership'])->group(function () {
            Route::get('/test/vendor/{vendor_id}', function ($vendor_id) {
                return response()->json(['status' => 'ok', 'vendor_id' => $vendor_id]);
            });

            Route::get('/test/resource/{resource}', function (TestResource $resource) {
                return response()->json(['status' => 'ok', 'id' => $resource->id]);
            });
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('test_resources');
        parent::tearDown();
    }

    public function test_vendor_can_access_own_resource_returns_200(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $resource = TestResource::create([
            'vendor_id' => $vendor->id,
            'title' => 'Own Cosplay Costume',
        ]);

        $response = $this->actingAs($user, 'vendor')
            ->get("/test/resource/{$resource->id}");

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok', 'id' => $resource->id]);
    }

    public function test_vendor_accessing_other_vendor_resource_returns_404_not_403(): void
    {
        $vendorA = Vendor::factory()->create(['is_active' => true]);
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create(['is_active' => true]);

        $resourceB = TestResource::create([
            'vendor_id' => $vendorB->id,
            'title' => 'Vendor B Cosplay Costume',
        ]);

        $response = $this->actingAs($userA, 'vendor')
            ->get("/test/resource/{$resourceB->id}");

        // IDOR prevention: MUST return 404, NEVER 403
        $response->assertStatus(404);

        // Verify IDOR attempt is logged in audit_logs
        $this->assertDatabaseHas('audit_logs', [
            'vendor_id' => $vendorA->id,
            'event' => 'idor_attempt_blocked',
            'entity_id' => (string) $resourceB->id,
        ]);
    }

    public function test_vendor_accessing_other_vendor_id_route_parameter_returns_404(): void
    {
        $vendorA = Vendor::factory()->create(['is_active' => true]);
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create(['is_active' => true]);

        // Accessing own vendor_id -> 200
        $responseOwn = $this->actingAs($userA, 'vendor')
            ->get("/test/vendor/{$vendorA->id}");
        $responseOwn->assertStatus(200);

        // Accessing other vendor_id -> 404
        $responseOther = $this->actingAs($userA, 'vendor')
            ->get("/test/vendor/{$vendorB->id}");
        $responseOther->assertStatus(404);
    }

    public function test_belongs_to_vendor_trait_auto_assigns_vendor_id(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $this->actingAs($user, 'vendor');

        $resource = TestResource::create(['title' => 'Auto Assigned Item']);

        $this->assertEquals($vendor->id, $resource->vendor_id);
    }

    public function test_belongs_to_vendor_scope_for_current_vendor_filters_records(): void
    {
        $vendorA = Vendor::factory()->create(['is_active' => true]);
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create(['is_active' => true]);

        TestResource::create(['vendor_id' => $vendorA->id, 'title' => 'Item A1']);
        TestResource::create(['vendor_id' => $vendorA->id, 'title' => 'Item A2']);
        TestResource::create(['vendor_id' => $vendorB->id, 'title' => 'Item B1']);

        $this->actingAs($userA, 'vendor');

        $accessibleItems = TestResource::forCurrentVendor()->get();

        $this->assertCount(2, $accessibleItems);
        $this->assertTrue($accessibleItems->every(fn ($item) => (int) $item->vendor_id === (int) $vendorA->id));
    }

    public function test_deactivated_vendor_is_blocked_by_middleware(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => false]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($user, 'vendor')
            ->get("/test/vendor/{$vendor->id}");

        $response->assertRedirect('/vendor/login');
        $this->assertGuest('vendor');
    }

    /**
     * TC-18 / IDOR-03: Vendor A generate signed URL dokumen Vendor B -> 404
     */
    public function test_vendor_a_generate_signed_url_dokumen_vendor_b_returns_404(): void
    {
        $vendorA = Vendor::factory()->create(['is_active' => true]);
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create(['is_active' => true]);
        $bookingB = Booking::factory()->create(['vendor_id' => $vendorB->id]);
        $documentB = IdentityDocument::factory()->create([
            'booking_id' => $bookingB->id,
            'renter_id' => $bookingB->renter_id,
        ]);

        $response = $this->actingAs($userA, 'vendor')
            ->postJson(route('vendor.identity-documents.signed-url', ['ulid' => $documentB->ulid]));

        $response->assertStatus(404);
    }
}
