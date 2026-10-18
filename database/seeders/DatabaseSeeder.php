<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorUser;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin Platform Account (for /admin panel)
        User::updateOrCreate(
            ['email' => 'admin@jagasewa.id'],
            [
                'name' => 'Admin JagaSewa',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Demo Vendor Toko
        $vendor = Vendor::updateOrCreate(
            ['slug' => 'tokyo-cosplay'],
            [
                'name' => 'Tokyo Cosplay Rental',
                'wa_number' => '081234567890',
                'email' => 'tokyo@cosplay.id',
                'is_active' => true,
            ]
        );

        // 3. Vendor Owner Account (for /vendor dashboard)
        VendorUser::updateOrCreate(
            ['email' => 'vendor@tokyo.id'],
            [
                'vendor_id' => $vendor->id,
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
                'role' => 'owner',
            ]
        );

        // 4. Sample Rental Items
        $itemYor = Item::updateOrCreate(
            [
                'vendor_id' => $vendor->id,
                'slug' => 'kostum-yor-forger',
            ],
            [
                'name' => 'Kostum Yor Forger - Spy x Family',
                'description' => 'Kostum Yor Forger lengkap dengan dress hitam premium, sarung tangan kulit imitasi, bando bunga emas, dan sepasang senjata props replica.',
                'size' => 'M (Lingkar Dada 88-92 cm, Pinggang 68-72 cm)',
                'included_items' => ['Dress hitam', 'Sarung tangan kulit', 'Bando mawar emas', 'Anting jarum', 'Props senjata 2 pcs'],
                'care_instructions' => 'Dry clean only. Jangan disetrika bagian kulit imitasi. Jangan gunakan pemutih pakaian.',
                'price_per_day' => 85000,
                'deposit_amount' => 100000,
                'fine_per_day' => 50000,
                'buffer_before' => 1,
                'buffer_after' => 2,
                'is_active' => true,
            ]
        );

        $itemTanjiro = Item::updateOrCreate(
            [
                'vendor_id' => $vendor->id,
                'slug' => 'kostum-tanjiro-kamado',
            ],
            [
                'name' => 'Kostum Tanjiro Kamado - Demon Slayer',
                'description' => 'Kostum Tanjiro Kamado lengkap dengan haori motif kotak hijau-hitam khas, seragam korps Demon Slayer berkerah tinggi, dan anting hanafuda akrilik.',
                'size' => 'L (Lingkar Dada 94-98 cm, Tinggi 165-175 cm)',
                'included_items' => ['Haori motif catur', 'Atasan seragam korps', 'Celana hakama', 'Leg wrap putih', 'Sabuk putih', 'Anting hanafuda'],
                'care_instructions' => 'Cuci manual dengan deterjen cair lembut. Keringkan di tempat teduh dan jangan diperas terlalu kencang.',
                'price_per_day' => 75000,
                'deposit_amount' => 100000,
                'fine_per_day' => 40000,
                'buffer_before' => 1,
                'buffer_after' => 1,
                'is_active' => true,
            ]
        );

        $itemKebaya = Item::updateOrCreate(
            [
                'vendor_id' => $vendor->id,
                'slug' => 'kebaya-kutubaru-maroon',
            ],
            [
                'name' => 'Kebaya Kutubaru Modern Maroon',
                'description' => 'Kebaya kutubaru modern beludru warna maroon elegan dengan bordir bunga benang emas, cocok untuk wisuda, kondangan, atau lamaran.',
                'size' => 'All Size (Fit to L, Lingkar Dada up to 96 cm)',
                'included_items' => ['Atasan kebaya beludru maroon', 'Kain batik bawahan wiru', 'Bros susun 3 emas', 'Kemben satin senada'],
                'care_instructions' => 'Dry clean only. Simpan digantung menggunakan hanger busa untuk menjaga tekstur beludru.',
                'price_per_day' => 120000,
                'deposit_amount' => 150000,
                'fine_per_day' => 60000,
                'buffer_before' => 1,
                'buffer_after' => 2,
                'is_active' => true,
            ]
        );

        // 5. Sample Renters & Bookings
        // Renter A - Booking Pending
        $renterA = Renter::create([
            'name' => 'Siti Nurhaliza',
            'phone' => '081298765432',
            'nik' => '3271123456780001',
            'address' => 'Jl. Dago No. 120, Coblong, Bandung',
        ]);

        Booking::create([
            'vendor_id' => $vendor->id,
            'item_id' => $itemTanjiro->id,
            'renter_id' => $renterA->id,
            'start_date' => Carbon::now('Asia/Jakarta')->addDays(3)->toDateString(),
            'end_date' => Carbon::now('Asia/Jakarta')->addDays(5)->toDateString(),
            'total_days' => 3,
            'total_price' => 75000 * 3,
            'deposit_paid' => 100000,
            'fine_amount' => 0,
            'status' => Booking::STATUS_PENDING,
            'idempotency_key' => (string) Str::uuid(),
            'renter_notes' => 'Acara cosplay festival kampus akhir pekan.',
            'pending_expires_at' => Carbon::now('Asia/Jakarta')->addHours(24),
        ]);

        // Renter B - Booking Approved
        $renterB = Renter::create([
            'name' => 'Dimas Prasetyo',
            'phone' => '085712349988',
            'nik' => '3271123456780002',
            'address' => 'Jl. Tebet Barat Raya No. 45, Jakarta Selatan',
        ]);

        Booking::create([
            'vendor_id' => $vendor->id,
            'item_id' => $itemYor->id,
            'renter_id' => $renterB->id,
            'start_date' => Carbon::now('Asia/Jakarta')->addDays(8)->toDateString(),
            'end_date' => Carbon::now('Asia/Jakarta')->addDays(10)->toDateString(),
            'total_days' => 3,
            'total_price' => 85000 * 3,
            'deposit_paid' => 100000,
            'fine_amount' => 0,
            'status' => Booking::STATUS_APPROVED,
            'idempotency_key' => (string) Str::uuid(),
            'renter_notes' => 'Foto studio pre-wedding bertema anime.',
            'approved_at' => Carbon::now('Asia/Jakarta')->subHours(2),
        ]);

        // Renter C - Booking Active (Sedang Dipinjam)
        $renterC = Renter::create([
            'name' => 'Anisa Rahmawati',
            'phone' => '087812345678',
            'nik' => '3271123456780003',
            'address' => 'Jl. Kaliurang KM 5 No. 18, Yogyakarta',
        ]);

        Booking::create([
            'vendor_id' => $vendor->id,
            'item_id' => $itemKebaya->id,
            'renter_id' => $renterC->id,
            'start_date' => Carbon::now('Asia/Jakarta')->subDay()->toDateString(),
            'end_date' => Carbon::now('Asia/Jakarta')->addDays(2)->toDateString(),
            'total_days' => 4,
            'total_price' => 120000 * 4,
            'deposit_paid' => 150000,
            'fine_amount' => 0,
            'status' => Booking::STATUS_ACTIVE,
            'idempotency_key' => (string) Str::uuid(),
            'renter_notes' => 'Acara wisuda sarjana di kampus UGM.',
            'approved_at' => Carbon::now('Asia/Jakarta')->subDays(2),
            'activated_at' => Carbon::now('Asia/Jakarta')->subDay(),
        ]);
    }
}
