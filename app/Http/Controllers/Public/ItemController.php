<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ItemController extends Controller
{
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
        $hasS3Config = ! empty(config('filesystems.disks.s3.bucket')) && ! empty(config('filesystems.disks.s3.key'));
        $diskName = $hasS3Config || app()->environment('testing') ? 's3' : 'public';

        if (! empty($item->photo_paths) && is_array($item->photo_paths)) {
            try {
                $disk = Storage::disk($diskName);
                foreach ($item->photo_paths as $path) {
                    try {
                        $photoSignedUrls[] = $disk->temporaryUrl($path, now()->addMinutes(60));
                    } catch (Throwable) {
                        try {
                            $photoSignedUrls[] = $disk->url($path);
                        } catch (Throwable) {
                            $photoSignedUrls[] = Storage::disk('public')->url($path);
                        }
                    }
                }
            } catch (Throwable) {
                $fallbackDisk = Storage::disk('public');
                foreach ($item->photo_paths as $path) {
                    $photoSignedUrls[] = $fallbackDisk->url($path);
                }
            }
        }

        return view('public.items.show', [
            'vendor' => $vendor,
            'item' => $item,
            'photos' => $photoSignedUrls,
        ]);
    }
}
