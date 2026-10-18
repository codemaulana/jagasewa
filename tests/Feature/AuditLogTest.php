<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Vendor;
use App\Models\VendorUser;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_record_audit_log_via_service(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $this->actingAs($user, 'vendor');

        $log = AuditService::log(
            event: 'booking_status_updated',
            entityType: 'booking',
            entityId: '01ARZ3NDEKTSV4RRFFQ69G5FAV',
            payload: [
                'status_before' => 'pending',
                'status_after' => 'approved',
                'item_name' => 'Kostum Raiden Shogun Size M',
            ]
        );

        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'vendor_id' => $vendor->id,
            'user_id' => $user->id,
            'event' => 'booking_status_updated',
            'entity_type' => 'booking',
            'entity_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV',
        ]);

        $this->assertEquals('pending', $log->payload['status_before']);
        $this->assertEquals('approved', $log->payload['status_after']);
        $this->assertEquals('Kostum Raiden Shogun Size M', $log->payload['item_name']);
        $this->assertNotNull($log->created_at);
    }

    public function test_audit_service_sanitizes_pii_from_payload(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $this->actingAs($user, 'vendor');

        $log = AuditService::log(
            event: 'document_uploaded',
            entityType: 'document',
            entityId: '01ARZ3NDEKTSV4RRFFQ69G5FAV',
            payload: [
                'nik' => '3201234567890001',
                'wa_number' => '081234567890',
                'phone_number' => '+6281234567890',
                'password' => 'supersecret',
                'storage_path' => 'private/documents/ktp_123.jpg',
                'raw_file_path' => 'C:/tmp/ktp.jpg',
                'card_number' => '4111111111111111',
                'booking_reference' => 'JS-20261018-001',
                'nested' => [
                    'secret_token' => 'xyz123',
                    'action' => 'verified',
                ],
            ]
        );

        // Sensitive keys must be REDACTED
        $this->assertEquals('[REDACTED]', $log->payload['nik']);
        $this->assertEquals('[REDACTED]', $log->payload['wa_number']);
        $this->assertEquals('[REDACTED]', $log->payload['phone_number']);
        $this->assertEquals('[REDACTED]', $log->payload['password']);
        $this->assertEquals('[REDACTED]', $log->payload['storage_path']);
        $this->assertEquals('[REDACTED]', $log->payload['raw_file_path']);
        $this->assertEquals('[REDACTED]', $log->payload['card_number']);
        $this->assertEquals('[REDACTED]', $log->payload['nested']['secret_token']);

        // Non-sensitive values must be preserved
        $this->assertEquals('JS-20261018-001', $log->payload['booking_reference']);
        $this->assertEquals('verified', $log->payload['nested']['action']);
    }

    public function test_audit_log_has_no_updated_at_column(): void
    {
        $this->assertFalse(Schema::hasColumn('audit_logs', 'updated_at'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'created_at'));
    }

    public function test_audit_logs_migration_can_be_rolled_back(): void
    {
        $this->assertTrue(Schema::hasTable('audit_logs'));

        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_10_18_000003_create_audit_logs_table.php',
        ])->assertSuccessful();

        $this->assertFalse(Schema::hasTable('audit_logs'));

        // Re-run migration for subsequent tests
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_10_18_000003_create_audit_logs_table.php',
        ])->assertSuccessful();
    }
}
