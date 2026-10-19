<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Services\BookingCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemAvailabilityController extends Controller
{
    /**
     * Get the calendar availability for a given item in a specified month.
     */
    public function __invoke(
        Request $request,
        string $ulid,
        BookingCalendarService $calendarService
    ): JsonResponse {
        // Resolve item strictly by public ULID (SEC-10 / Anti-enumeration)
        $item = Item::where('ulid', $ulid)->first();

        // 404 if item does not exist, is inactive, or vendor is inactive
        if (! $item || ! $item->is_active || ! $item->vendor || ! $item->vendor->is_active) {
            abort(404, 'Barang tidak ditemukan atau tidak aktif.');
        }

        // Validate month parameter (format: YYYY-MM)
        $month = $request->query('month');

        if (! $month) {
            $month = Carbon::now('Asia/Jakarta')->format('Y-m');
        } elseif (! is_string($month) || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return response()->json([
                'message' => 'Format bulan tidak valid. Gunakan format YYYY-MM (contoh: 2026-10).',
                'errors' => [
                    'month' => ['Format bulan harus YYYY-MM.'],
                ],
            ], 422);
        }

        $days = $calendarService->getMonthAvailability($item, $month);

        return response()->json($days);
    }
}
