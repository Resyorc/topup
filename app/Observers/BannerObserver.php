<?php

namespace App\Observers;

use App\Models\Banner;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class BannerObserver
{
    public function saved(Banner $banner): void
    {
        $image = $banner->image;

        if (! $image || Str::endsWith($image, '.webp')) {
            return;
        }

        if (! $banner->wasRecentlyCreated && ! $banner->wasChanged('image')) {
            return;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($image)) {
            return;
        }

        $newPath = Str::beforeLast($image, '.') . '.webp';

        try {
            Image::read($disk->path($image))
                ->scaleDown(width: 1600)
                ->toWebp(82)
                ->save($disk->path($newPath));

            $disk->delete($image);

            $banner->updateQuietly(['image' => $newPath]);
        } catch (\Throwable $e) {
            // WebP not supported (e.g. local dev without --with-webp), skip conversion
            logger()->warning('Banner WebP conversion failed: ' . $e->getMessage());
        }
    }
}
