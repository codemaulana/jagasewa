<?php

namespace Tests\Unit;

use App\Jobs\AutoDeleteDocumentsJob;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\IdentityDocument;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AutoDeleteDocumentsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    protected function createBookingWithDocument(
        string $bookingStatus,
        Carbon $bookingUpdatedAt,
        ?Carbon $deleteScheduledAt = null,
        ?Carbon $docCreatedAt = null
    ): array {
        $vendor = Vendor::factory()->create();
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $renter = Renter::factory()->create();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => $bookingStatus,
            'created_at' => $docCreatedAt ?? Carbon::now('Asia/Jakarta')->subDays(10),
            'updated_at' => $bookingUpdatedAt,
        ]);

        $filePath = 'identity-documents/'.(string) Str::uuid().'.jpg';
        Storage::disk('s3')->put($filePath, 'fake image content');

        $document = IdentityDocument::create([
            'booking_id' => $booking->id,
            'renter_id' => $renter->id,
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'storage_path' => $filePath,
            'storage_disk' => 's3',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'consent_given' => true,
            'consent_version' => 'v1.0',
            'consent_at' => Carbon::now('Asia/Jakarta')->subDays(10),
            'delete_scheduled_at' => $deleteScheduledAt ?? Carbon::now('Asia/Jakarta')->addDays(7),
        ]);

        if ($docCreatedAt) {
            $document->created_at = $docCreatedAt;
            $document->save();
        }

        return [$vendor, $booking, $document, $filePath];
    }

    /**
     * TC-36: AutoDeleteDocumentsJob -> file > 7 hari setelah booking closed terhapus.
     */
    public function test_auto_delete_purges_document_for_booking_closed_more_than_seven_days_ago(): void
    {
        // Booking closed 8 days ago
        $closedAt = Carbon::now('Asia/Jakarta')->subDays(8);
        [$vendor, $booking, $document, $filePath] = $this->createBookingWithDocument(
            Booking::STATUS_CLOSED,
            $closedAt,
            Carbon::now('Asia/Jakarta')->subDay() // scheduled yesterday
        );

        Storage::disk('s3')->assertExists($filePath);

        $purgedCount = (new AutoDeleteDocumentsJob)->handle();

        $this->assertEquals(1, $purgedCount);
        Storage::disk('s3')->assertMissing($filePath);

        $document->refresh();
        $this->assertTrue($document->trashed());
        $this->assertNotNull($document->deleted_at);
    }

    /**
     * TC-36 (Cancelled): AutoDeleteDocumentsJob -> file > 3 hari setelah booking cancelled/rejected terhapus.
     */
    public function test_auto_delete_purges_document_for_booking_cancelled_more_than_three_days_ago(): void
    {
        // Booking cancelled 4 days ago
        $cancelledAt = Carbon::now('Asia/Jakarta')->subDays(4);
        [$vendor, $booking, $document, $filePath] = $this->createBookingWithDocument(
            Booking::STATUS_CANCELLED_TIMEOUT,
            $cancelledAt,
            Carbon::now('Asia/Jakarta')->subDay()
        );

        Storage::disk('s3')->assertExists($filePath);

        $purgedCount = (new AutoDeleteDocumentsJob)->handle();

        $this->assertEquals(1, $purgedCount);
        Storage::disk('s3')->assertMissing($filePath);

        $document->refresh();
        $this->assertTrue($document->trashed());
    }

    /**
     * TC-36 (Rejected): AutoDeleteDocumentsJob -> file > 3 hari setelah rejected terhapus.
     */
    public function test_auto_delete_purges_document_for_booking_rejected_more_than_three_days_ago(): void
    {
        $rejectedAt = Carbon::now('Asia/Jakarta')->subDays(5);
        [$vendor, $booking, $document, $filePath] = $this->createBookingWithDocument(
            Booking::STATUS_REJECTED,
            $rejectedAt
        );

        Storage::disk('s3')->assertExists($filePath);

        $purgedCount = (new AutoDeleteDocumentsJob)->handle();

        $this->assertEquals(1, $purgedCount);
        Storage::disk('s3')->assertMissing($filePath);

        $document->refresh();
        $this->assertTrue($document->trashed());
    }

    /**
     * TC-37: AutoDeleteDocumentsJob -> audit_log tidak mengandung path file setelah hapus.
     */
    public function test_auto_delete_records_audit_log_without_raw_storage_path_or_pii(): void
    {
        $closedAt = Carbon::now('Asia/Jakarta')->subDays(10);
        [$vendor, $booking, $document, $filePath] = $this->createBookingWithDocument(
            Booking::STATUS_CLOSED,
            $closedAt,
            Carbon::now('Asia/Jakarta')->subDays(3)
        );

        (new AutoDeleteDocumentsJob)->handle();

        // Check audit log exists
        $auditLog = AuditLog::where('event', 'document_deleted')
            ->where('entity_type', 'identity_document')
            ->where('entity_id', (string) $document->id)
            ->first();

        $this->assertNotNull($auditLog, 'Audit log document_deleted harus tercatat');
        $this->assertEquals($vendor->id, $auditLog->vendor_id);

        $payload = $auditLog->payload;
        $this->assertIsArray($payload);
        $this->assertEquals($booking->id, $payload['booking_id']);
        $this->assertEquals('ktp', $payload['doc_type']);
        $this->assertArrayHasKey('deleted_at', $payload);

        // SEC-31 & TC-37: Raw path and PII must NOT appear in audit log
        $payloadJson = json_encode($payload);
        $this->assertStringNotContainsString($filePath, $payloadJson, 'Audit log payload tidak boleh memuat path storage');
        $this->assertStringNotContainsString('identity-documents', $payloadJson, 'Audit log payload tidak boleh memuat folder storage');
        $this->assertArrayNotHasKey('storage_path', $payload);
        $this->assertArrayNotHasKey('raw_file_path', $payload);
    }

    /**
     * TC-38: Fallback -> file > 90 hari setelah upload (booking tidak pernah closed) terhapus.
     */
    public function test_auto_delete_purges_fallback_document_created_more_than_ninety_days_ago(): void
    {
        // Unclosed booking created 95 days ago
        $ninetyFiveDaysAgo = Carbon::now('Asia/Jakarta')->subDays(95);
        [$vendor, $booking, $document, $filePath] = $this->createBookingWithDocument(
            Booking::STATUS_PENDING,
            $ninetyFiveDaysAgo,
            Carbon::now('Asia/Jakarta')->addDays(10), // even if scheduled in future, fallback catches it
            $ninetyFiveDaysAgo
        );

        Storage::disk('s3')->assertExists($filePath);

        $purgedCount = (new AutoDeleteDocumentsJob)->handle();

        $this->assertEquals(1, $purgedCount);
        Storage::disk('s3')->assertMissing($filePath);

        $document->refresh();
        $this->assertTrue($document->trashed());
    }

    /**
     * Verify that documents within active retention windows are NOT deleted.
     */
    public function test_auto_delete_does_not_purge_non_expired_documents(): void
    {
        // 1. Closed only 2 days ago (< 7 days)
        [$v1, $b1, $d1, $f1] = $this->createBookingWithDocument(
            Booking::STATUS_CLOSED,
            Carbon::now('Asia/Jakarta')->subDays(2),
            Carbon::now('Asia/Jakarta')->addDays(5)
        );

        // 2. Cancelled only 1 day ago (< 3 days)
        [$v2, $b2, $d2, $f2] = $this->createBookingWithDocument(
            Booking::STATUS_CANCELLED_TIMEOUT,
            Carbon::now('Asia/Jakarta')->subDay(),
            Carbon::now('Asia/Jakarta')->addDays(2)
        );

        // 3. Active booking with document created 10 days ago (< 90 days fallback)
        [$v3, $b3, $d3, $f3] = $this->createBookingWithDocument(
            Booking::STATUS_ACTIVE,
            Carbon::now('Asia/Jakarta')->subDays(10),
            Carbon::now('Asia/Jakarta')->addDays(14),
            Carbon::now('Asia/Jakarta')->subDays(10)
        );

        $purgedCount = (new AutoDeleteDocumentsJob)->handle();

        $this->assertEquals(0, $purgedCount);

        Storage::disk('s3')->assertExists($f1);
        Storage::disk('s3')->assertExists($f2);
        Storage::disk('s3')->assertExists($f3);

        $this->assertFalse($d1->refresh()->trashed());
        $this->assertFalse($d2->refresh()->trashed());
        $this->assertFalse($d3->refresh()->trashed());
    }

    /**
     * Test console command documents:auto-delete execution.
     */
    public function test_console_command_documents_auto_delete_executes_successfully(): void
    {
        $closedAt = Carbon::now('Asia/Jakarta')->subDays(10);
        [$vendor, $booking, $document, $filePath] = $this->createBookingWithDocument(
            Booking::STATUS_CLOSED,
            $closedAt,
            Carbon::now('Asia/Jakarta')->subDays(2)
        );

        $this->artisan('documents:auto-delete')
            ->expectsOutput('Checking for expired identity documents...')
            ->expectsOutput('Successfully purged 1 expired identity document(s).')
            ->assertExitCode(0);

        Storage::disk('s3')->assertMissing($filePath);
        $this->assertTrue($document->refresh()->trashed());
    }

    /**
     * Test that documents:auto-delete command is registered in console schedule at 02:00.
     */
    public function test_scheduler_registers_documents_auto_delete(): void
    {
        $schedule = app(Schedule::class);

        $events = collect($schedule->events())->filter(function (Event $event) {
            return str_contains($event->command, 'documents:auto-delete');
        });

        $this->assertCount(1, $events, 'Command documents:auto-delete harus terjadwal di schedule');

        /** @var Event $event */
        $event = $events->first();
        // 02:00 daily cron expression is '0 2 * * *'
        $this->assertEquals('0 2 * * *', $event->expression);
    }
}
