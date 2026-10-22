<?php

namespace Tests\Feature;

use App\Filament\Vendor\Resources\Renters\Pages\ListRenters;
use App\Filament\Vendor\Resources\Renters\Pages\ViewRenter;
use App\Helpers\WaNotificationHelper;
use App\Models\BlacklistEntry;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Subscription;
use App\Models\Vendor;
use App\Models\VendorRenter;
use App\Models\VendorUser;
use App\Services\BlacklistService;
use App\Services\FineCalculatorService;
use App\Services\IdentityHashService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class Gelombang2TrustAndMoneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        Storage::fake('s3');
        Storage::fake('public');
    }

    /**
     * Test 1: Vendor can list only own customers with isolation, view profile, and edit internal notes.
     */
    public function test_vendor_customer_list_and_internal_notes_with_isolation(): void
    {
        $vendorA = Vendor::factory()->create(['name' => 'Vendor A']);
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create(['name' => 'Vendor B']);

        $renterA = Renter::factory()->create(['name' => 'Budi Santoso', 'phone' => '081234567890']);
        $renterB = Renter::factory()->create(['name' => 'Dewi Lestari', 'phone' => '089876543210']);

        $itemA = Item::factory()->create(['vendor_id' => $vendorA->id]);
        $itemB = Item::factory()->create(['vendor_id' => $vendorB->id]);

        // Booking A for Vendor A
        Booking::factory()->create([
            'vendor_id' => $vendorA->id,
            'renter_id' => $renterA->id,
            'item_id' => $itemA->id,
            'status' => Booking::STATUS_CLOSED,
            'total_price' => 250000,
        ]);

        // Booking B for Vendor B
        Booking::factory()->create([
            'vendor_id' => $vendorB->id,
            'renter_id' => $renterB->id,
            'item_id' => $itemB->id,
            'status' => Booking::STATUS_CLOSED,
            'total_price' => 300000,
        ]);

        $this->actingAs($userA, 'vendor');
        session(['vendor_id' => $vendorA->id]);

        // Vendor A sees Renter A in customer table, but NOT Renter B
        Livewire::test(ListRenters::class)
            ->assertSuccessful()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Dewi Lestari');

        // Vendor A views Renter A and updates internal memo
        $viewComponent = Livewire::test(ViewRenter::class, ['record' => $renterA->ulid])
            ->assertSuccessful()
            ->assertSee('Budi Santoso')
            ->assertSee('Riwayat Pesanan Sewa');

        // Call edit_notes action
        $viewComponent->callAction('edit_notes', [
            'reliability_tag' => VendorRenter::RELIABILITY_TRUSTED,
            'internal_notes' => 'Penyewa langganan, sangat bertanggung jawab dan ramah.',
        ])->assertHasNoActionErrors();

        $profile = VendorRenter::where('vendor_id', $vendorA->id)->where('renter_id', $renterA->id)->first();
        $this->assertNotNull($profile);
        $this->assertEquals(VendorRenter::RELIABILITY_TRUSTED, $profile->reliability_tag);
        $this->assertEquals('Penyewa langganan, sangat bertanggung jawab dan ramah.', $profile->internal_notes);
    }

    /**
     * Test 2: Renter reliability is automatically recalculated based on booking history.
     */
    public function test_renter_reliability_recalculated_automatically(): void
    {
        $vendor = Vendor::factory()->create();
        $renter = Renter::factory()->create();
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);

        $profile = VendorRenter::create([
            'vendor_id' => $vendor->id,
            'renter_id' => $renter->id,
            'reliability_tag' => VendorRenter::RELIABILITY_NORMAL,
        ]);

        // 2 closed rentals without damage -> trusted
        Booking::factory()->count(2)->create([
            'vendor_id' => $vendor->id,
            'renter_id' => $renter->id,
            'item_id' => $item->id,
            'status' => Booking::STATUS_CLOSED,
            'total_price' => 200000,
        ]);

        $profile->recalculateMetrics();
        $profile->refresh();

        $this->assertEquals(VendorRenter::RELIABILITY_TRUSTED, $profile->reliability_tag);
        $this->assertEquals(2, $profile->total_completed_rentals);
        $this->assertEquals(400000, $profile->total_spent);
    }

    /**
     * Test 3: Shared Blacklist Search respects Free 10-check quota and allows Pro unlimited checks.
     */
    public function test_shared_blacklist_search_quota_free_vs_pro(): void
    {
        $vendor = Vendor::factory()->create(['name' => 'Toko Rental Biasa']);
        $service = app(BlacklistService::class);

        // Populate a shared community blacklist entry
        $hash = IdentityHashService::hashIdentity(BlacklistEntry::TYPE_WA, '081234567890');
        BlacklistEntry::create([
            'vendor_id' => Vendor::factory()->create()->id,
            'identity_type' => BlacklistEntry::TYPE_WA,
            'identity_hash' => $hash,
            'masked_identity' => '62812****7890',
            'scope' => BlacklistEntry::SCOPE_SHARED,
            'verification_status' => BlacklistEntry::STATUS_VERIFIED,
            'reason' => 'Membawa kabur kamera dan memblokir kontak',
        ]);

        // Free vendor starts with 10 checks
        $this->assertEquals(10, $service->getRemainingMonthlyChecks($vendor));

        // Perform 10 searches successfully
        for ($i = 1; $i <= 10; $i++) {
            $result = $service->searchBlacklist($vendor, BlacklistEntry::TYPE_WA, '081234567890');
            $this->assertTrue($result['success']);
            $this->assertTrue($result['has_match']);
            $this->assertStringContainsString('****', $result['matches'][0]['masked_identity']);
            // Never expose other reporter vendor ID
            $this->assertEquals('Laporan Komunitas Terverifikasi', $result['matches'][0]['source']);
        }

        $this->assertEquals(0, $service->getRemainingMonthlyChecks($vendor));

        // 11th search throws ValidationException for Free tier
        $this->expectException(ValidationException::class);
        $service->searchBlacklist($vendor, BlacklistEntry::TYPE_WA, '081234567890');
    }

    /**
     * Test 4: Pro vendor can search without quota and report to shared community blacklist.
     */
    public function test_pro_vendor_shared_blacklist_reporting_and_dispute(): void
    {
        $vendor = Vendor::factory()->create();
        Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now('Asia/Jakarta'),
            'current_period_end' => now('Asia/Jakarta')->addDays(30),
        ]);

        $service = app(BlacklistService::class);
        $this->assertEquals(-1, $service->getRemainingMonthlyChecks($vendor));

        // Pro vendor reports new scammer to shared blacklist
        $entry = $service->reportToSharedBlacklist($vendor, [
            'identity_type' => BlacklistEntry::TYPE_WA,
            'raw_identity' => '087711223344',
            'reason' => 'Barang dikembalikan rusak parah dan menolak membayar ganti rugi',
            'evidence' => 'Nota bengkel resmi',
        ]);

        $this->assertEquals(BlacklistEntry::SCOPE_SHARED, $entry->scope);
        $this->assertEquals(BlacklistEntry::STATUS_VERIFIED, $entry->verification_status);
        $this->assertEquals('6287****3344', $entry->masked_identity);

        // Dispute entry
        $disputed = $service->submitDispute($entry->ulid, 'Saya sudah menyelesaikan pembayaran via transfer.');
        $this->assertTrue($disputed);

        $entry->refresh();
        $this->assertTrue($entry->is_disputed);
        $this->assertEquals(BlacklistEntry::STATUS_DISPUTED, $entry->verification_status);
    }

    /**
     * Test 5: Advanced fine calculator supports hourly rates, grace hours, and damage/loss deductions.
     */
    public function test_advanced_fine_calculator_hourly_and_loss_deductions(): void
    {
        $calculator = app(FineCalculatorService::class);

        // Scheduled return: 2026-10-20 18:00
        // Actual return: 2026-10-20 22:00 (4 hours late)
        // Grace period: 2 hours -> 2 hours billed
        // Hourly rate: Rp 25.000 / hour -> Late fine: Rp 50.000
        // Deposit: Rp 200.000, Damage: Rp 30.000, Loss: Rp 20.000 -> Total deduction: Rp 100.000
        // Remaining refund: Rp 100.000
        $result = $calculator->calculateAdvanced(
            endDate: '2026-10-20 18:00:00',
            actualReturnDate: '2026-10-20 22:00:00',
            fineRate: 25000,
            deposit: 200000,
            damageDeduction: 30000,
            lossDeduction: 20000,
            rateType: 'hourly',
            graceHours: 2
        );

        $this->assertEquals(50000, $result->lateFine);
        $this->assertEquals(50000, $result->damageDeduction); // 30k damage + 20k loss
        $this->assertEquals(100000, $result->totalDeduction);
        $this->assertEquals(100000, $result->refund);
        $this->assertFalse($result->hasShortfall);
    }

    /**
     * Test 6: Document generation (Invoice & Agreement) blocked on Free, allowed on Pro.
     */
    public function test_document_generation_entitlement_and_pdf_binary(): void
    {
        $vendor = Vendor::factory()->create(['name' => 'Rental Kostum Jogja']);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $renter = Renter::factory()->create();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_APPROVED,
            'total_price' => 350000,
            'deposit_paid' => 150000,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        // Free tier -> 403 Forbidden
        $response = $this->get(route('vendor.bookings.invoice', $booking));
        $response->assertStatus(403);

        $responseAgreement = $this->get(route('vendor.bookings.agreement', $booking));
        $responseAgreement->assertStatus(403);

        // Upgrade vendor to Pro
        Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now('Asia/Jakarta'),
            'current_period_end' => now('Asia/Jakarta')->addDays(30),
        ]);

        // Pro tier -> printable HTML rendered successfully
        $this->get(route('vendor.bookings.invoice', $booking))
            ->assertSuccessful()
            ->assertSee('INVOICE')
            ->assertSee($booking->reference_code);

        $this->get(route('vendor.bookings.agreement', $booking))
            ->assertSuccessful()
            ->assertSee('Surat Perjanjian Sewa Menyewa Barang')
            ->assertSee($booking->reference_code);

        // Pro tier -> PDF download binary generated
        $pdfResponse = $this->get(route('vendor.bookings.invoice', $booking).'?download=pdf');
        $pdfResponse->assertSuccessful();
        $pdfResponse->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-1.4', $pdfResponse->getContent());
        $this->assertStringContainsString('%%EOF', $pdfResponse->getContent());
    }

    /**
     * Test 7: WhatsApp notification URL replaces dynamic template tags.
     */
    public function test_wa_template_tag_replacements(): void
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Studio Foto Keren',
            'custom_wa_templates' => [
                'approved' => 'Halo {nama}, sewa {barang} tanggal {tanggal} total {total} telah disetujui. Ref: {kode_booking}',
            ],
        ]);
        Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now('Asia/Jakarta'),
            'current_period_end' => now('Asia/Jakarta')->addDays(30),
        ]);

        $item = Item::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Kamera Fuji X-T4']);
        $renter = Renter::factory()->create(['name' => 'Rian Ardianto', 'phone' => '081299887766']);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_APPROVED,
            'total_price' => 300000,
        ]);

        $waUrl = WaNotificationHelper::generateRenterChatUrl($booking, 'approved');

        $this->assertNotNull($waUrl);
        $this->assertStringStartsWith('https://wa.me/6281299887766', $waUrl);
        $this->assertStringContainsString(rawurlencode('Halo Rian Ardianto'), $waUrl);
        $this->assertStringContainsString(rawurlencode('Kamera Fuji X-T4'), $waUrl);
        $this->assertStringContainsString(rawurlencode('Rp 300.000'), $waUrl);
        $this->assertStringContainsString(rawurlencode($booking->reference_code), $waUrl);
    }
}
