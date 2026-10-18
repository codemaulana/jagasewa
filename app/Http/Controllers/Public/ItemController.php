<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ItemController extends Controller
{
    /**
     * Display the public vendor catalog page.
     */
    public function catalog(string $vendor_slug): View
    {
        /** @var Vendor $vendor */
        $vendor = Vendor::where('slug', $vendor_slug)
            ->where('is_active', true)
            ->firstOrFail();

        $items = Item::where('vendor_id', $vendor->id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $items->getCollection()->transform(function (Item $item) {
            $firstPhoto = ! empty($item->photo_paths) && is_array($item->photo_paths) ? $item->photo_paths[0] : null;
            $item->primary_photo_url = $this->resolvePhotoUrl($firstPhoto);

            return $item;
        });

        return view('public.vendor.catalog', [
            'vendor' => $vendor,
            'items' => $items,
        ]);
    }

    /**
     * Display the public item detail page.
     */
    public function show(string $vendor_slug, string $item_slug): View
    {
        /** @var Vendor $vendor */
        $vendor = Vendor::where('slug', $vendor_slug)
            ->where('is_active', true)
            ->firstOrFail();

        /** @var Item $item */
        $item = Item::where('vendor_id', $vendor->id)
            ->where('slug', $item_slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Generate temporary signed URLs for photos (TTL 60 minutes)
        $photoSignedUrls = [];
        if (! empty($item->photo_paths) && is_array($item->photo_paths)) {
            foreach ($item->photo_paths as $path) {
                if ($url = $this->resolvePhotoUrl($path)) {
                    $photoSignedUrls[] = $url;
                }
            }
        }

        return view('public.items.show', [
            'vendor' => $vendor,
            'item' => $item,
            'photos' => $photoSignedUrls,
        ]);
    }

    /**
     * Resolve a signed or public URL for a photo path.
     */
    protected function resolvePhotoUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $hasS3Config = ! empty(config('filesystems.disks.s3.bucket')) && ! empty(config('filesystems.disks.s3.key'));
        $diskName = $hasS3Config || app()->environment('testing') ? 's3' : 'public';

        try {
            $disk = Storage::disk($diskName);
            try {
                return $disk->temporaryUrl($path, now()->addMinutes(60));
            } catch (Throwable) {
                try {
                    return $disk->url($path);
                } catch (Throwable) {
                    return Storage::disk('public')->url($path);
                }
            }
        } catch (Throwable) {
            return Storage::disk('public')->url($path);
        }
    }

    /**
     * Display the public booking success page.
     */
    public function bookingSuccess(string $ulid): View
    {
        $booking = Booking::with(['item', 'vendor', 'renter'])
            ->where('ulid', $ulid)
            ->firstOrFail();

        return view('public.bookings.success', [
            'booking' => $booking,
        ]);
    }
}
