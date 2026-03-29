<?php

namespace App\Observers;

use App\Models\Game;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class GameObserver
{
    public function saved(Game $game): void
    {
        if ($game->image && Str::endsWith($game->image, '.webp') && ($game->wasRecentlyCreated || $game->wasChanged('image'))) {
            $this->generateSmallVariant(Storage::disk('public'), $game->image);
        }
    }

    private function generateSmallVariant($disk, string $relativePath, int $smallWidth = 188): void
    {
        $smPath = Str::beforeLast($relativePath, '.webp') . '-sm.webp';

        if ($disk->exists($smPath) || ! $disk->exists($relativePath)) {
            return;
        }

        Image::read($disk->path($relativePath))
            ->scaleDown(width: $smallWidth)
            ->toWebp(80)
            ->save($disk->path($smPath));
    }
}
