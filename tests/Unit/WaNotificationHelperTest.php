<?php

namespace Tests\Unit;

use App\Helpers\WaNotificationHelper;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Vendor;
use Carbon\Carbon;
use Tests\TestCase;

class WaNotificationHelperTest extends TestCase
{
    public function test_normalize_phone_number_formats(): void
    {
        $this->assertEquals('628123456789', WaNotificationHelper::normalizePhoneNumber('08123456789'));
        $this->assertEquals('6281234567890', WaNotificationHelper::normalizePhoneNumber('+6281234567890'));
        $this->assertEquals('6281234567890', WaNotificationHelper::normalizePhoneNumber('6281234567890'));
        $this->assertEquals('6281234567890', WaNotificationHelper::normalizePhoneNumber('0812-3456-7890'));
        $this->assertEquals('6281234567890', WaNotificationHelper::normalizePhoneNumber('+62 812-3456-7890'));
        $this->assertEquals('628123456789', WaNotificationHelper::normalizePhoneNumber('8123456789'));

        // Invalid cases
        $this->assertNull(WaNotificationHelper::normalizePhoneNumber(null));
        $this->assertNull(WaNotificationHelper::normalizePhoneNumber(''));
        $this->assertNull(WaNotificationHelper::normalizePhoneNumber('12345')); // Too short
        $this->assertNull(WaNotificationHelper::normalizePhoneNumber('abcde'));
    }

    public function test_generate_vendor_chat_url_formats_message_and_escapes_url(): void
    {
        $vendor = new Vendor([
            'name' => 'Studio Cosplay Akihabara',
            'wa_number' => '081234567890',
        ]);

        $item = new Item([
            'name' => 'Kostum Raiden Shogun (M)',
            'price_per_day' => 150000,
        ]);

        $renter = new Renter([
            'name' => 'Budi Pratama',
            'phone' => '081987654321',
        ]);

        $booking = new Booking([
            'reference_code' => 'JS-20261019-ABCD',
            'start_date' => Carbon::parse('2026-10-25'),
            'end_date' => Carbon::parse('2026-10-27'),
            'total_days' => 3,
            'total_price' => 450000,
        ]);

        $booking->setRelation('vendor', $vendor);
        $booking->setRelation('item', $item);
        $booking->setRelation('renter', $renter);

        $url = WaNotificationHelper::generateVendorChatUrl($booking);

        $this->assertNotNull($url);
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $url);

        $decodedQuery = rawurldecode(parse_url($url, PHP_URL_QUERY));
        $this->assertStringContainsString('JS-20261019-ABCD', $decodedQuery);
        $this->assertStringContainsString('Kostum Raiden Shogun (M)', $decodedQuery);
        $this->assertStringContainsString('Budi Pratama', $decodedQuery);
        $this->assertStringContainsString('Rp 450.000', $decodedQuery);
        $this->assertStringContainsString('Studio Cosplay Akihabara', $decodedQuery);
    }

    public function test_generate_vendor_chat_url_returns_null_when_vendor_phone_empty(): void
    {
        $vendor = new Vendor([
            'name' => 'Vendor Tanpa Nomor',
            'wa_number' => null,
        ]);

        $booking = new Booking([
            'reference_code' => 'JS-20261019-VOID',
        ]);
        $booking->setRelation('vendor', $vendor);

        $url = WaNotificationHelper::generateVendorChatUrl($booking);

        $this->assertNull($url);
    }

    public function test_generate_renter_chat_url_formats_approved_and_rejected_messages(): void
    {
        $vendor = new Vendor(['name' => 'Studio Foto Bandung']);
        $item = new Item(['name' => 'Sony A7 Mark III']);
        $renter = new Renter([
            'name' => 'Siti Aisyah',
            'phone' => '081399998888',
        ]);

        $booking = new Booking([
            'reference_code' => 'JS-20261019-SITI',
            'start_date' => Carbon::parse('2026-10-25'),
            'end_date' => Carbon::parse('2026-10-26'),
            'total_days' => 2,
        ]);
        $booking->setRelation('vendor', $vendor);
        $booking->setRelation('item', $item);
        $booking->setRelation('renter', $renter);

        // Approved URL
        $approvedUrl = WaNotificationHelper::generateRenterChatUrl($booking, 'approved');
        $this->assertNotNull($approvedUrl);
        $this->assertStringStartsWith('https://wa.me/6281399998888?text=', $approvedUrl);
        $decodedApproved = rawurldecode(parse_url($approvedUrl, PHP_URL_QUERY));
        $this->assertStringContainsString('DISETUJUI', $decodedApproved);
        $this->assertStringContainsString('Sony A7 Mark III', $decodedApproved);

        // Rejected URL
        $rejectedUrl = WaNotificationHelper::generateRenterChatUrl($booking, 'rejected');
        $this->assertNotNull($rejectedUrl);
        $decodedRejected = rawurldecode(parse_url($rejectedUrl, PHP_URL_QUERY));
        $this->assertStringContainsString('belum dapat kami setujui', $decodedRejected);
    }
}
