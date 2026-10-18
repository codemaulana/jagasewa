<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\IdentityDocument;
use App\Models\Renter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IdentityDocumentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_identity_document_with_factory(): void
    {
        $doc = IdentityDocument::factory()->create([
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'storage_path' => 'identity-documents/ktp-sample-01.jpg',
            'storage_disk' => 's3',
            'file_size' => 1024000,
            'mime_type' => 'image/jpeg',
            'consent_given' => true,
            'consent_version' => 'v1.0',
        ]);

        $this->assertDatabaseHas('identity_documents', [
            'id' => $doc->id,
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'storage_path' => 'identity-documents/ktp-sample-01.jpg',
            'consent_given' => 1,
            'consent_version' => 'v1.0',
        ]);

        $this->assertNotNull($doc->ulid);
        $this->assertEquals(26, strlen($doc->ulid));
        $this->assertNotNull($doc->consent_at);
        $this->assertNotNull($doc->delete_scheduled_at);
        $this->assertNull($doc->deleted_at);
        $this->assertTrue($doc->isKtp());
        $this->assertFalse($doc->isSelfie());
    }

    public function test_identity_document_factory_states(): void
    {
        $ktpDoc = IdentityDocument::factory()->ktp()->create();
        $this->assertEquals(IdentityDocument::DOC_TYPE_KTP, $ktpDoc->doc_type);
        $this->assertTrue($ktpDoc->isKtp());

        $selfieDoc = IdentityDocument::factory()->selfie()->create();
        $this->assertEquals(IdentityDocument::DOC_TYPE_SELFIE, $selfieDoc->doc_type);
        $this->assertTrue($selfieDoc->isSelfie());

        $readyDoc = IdentityDocument::factory()->readyForDeletion()->create();
        $this->assertTrue($readyDoc->delete_scheduled_at->isPast());

        $deletedDoc = IdentityDocument::factory()->deleted()->create();
        $this->assertNotNull($deletedDoc->deleted_at);
    }

    public function test_relationships_work(): void
    {
        $renter = Renter::factory()->create();
        $booking = Booking::factory()->create(['renter_id' => $renter->id]);

        $doc = IdentityDocument::factory()->create([
            'booking_id' => $booking->id,
            'renter_id' => $renter->id,
        ]);

        // BelongsTo relationships
        $this->assertInstanceOf(Booking::class, $doc->booking);
        $this->assertEquals($booking->id, $doc->booking->id);

        $this->assertInstanceOf(Renter::class, $doc->renter);
        $this->assertEquals($renter->id, $doc->renter->id);

        // HasMany relationships
        $this->assertTrue($booking->identityDocuments->contains($doc));
        $this->assertTrue($renter->identityDocuments->contains($doc));
    }

    public function test_deleting_booking_cascades_to_identity_documents(): void
    {
        $booking = Booking::factory()->create();
        $doc = IdentityDocument::factory()->create(['booking_id' => $booking->id]);

        $this->assertDatabaseHas('identity_documents', ['id' => $doc->id]);

        $booking->delete();

        $this->assertDatabaseMissing('identity_documents', ['id' => $doc->id]);
    }

    public function test_soft_deletes_identity_document(): void
    {
        $doc = IdentityDocument::factory()->create();

        $doc->delete();

        $this->assertSoftDeleted('identity_documents', ['id' => $doc->id]);
        $this->assertNull(IdentityDocument::find($doc->id));
        $this->assertNotNull(IdentityDocument::withTrashed()->find($doc->id));
    }

    public function test_identity_documents_migration_can_be_rolled_back_cleanly(): void
    {
        $this->assertTrue(Schema::hasTable('identity_documents'));

        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_10_18_000007_create_identity_documents_table.php',
        ])->assertSuccessful();

        $this->assertFalse(Schema::hasTable('identity_documents'));

        // Re-run for subsequent tests
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_10_18_000007_create_identity_documents_table.php',
        ])->assertSuccessful();

        $this->assertTrue(Schema::hasTable('identity_documents'));
    }
}
