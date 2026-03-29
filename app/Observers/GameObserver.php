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
        $updates = [];

        if ($this->fieldChanged($game, 'image')) {
            if ($path = $this->convertToWebp($game->image, 400)) {
                $updates['image'] = $path;
            }
        } elseif ($game->image && Str::endsWith($game->image, '.webp') && ($game->wasRecentlyCreated || $game->wasChanged('image'))) {
            $this->generateSmallVariant(Storage::disk('public'), $game->image);
        }

        if ($this->fieldChanged($game, 'thumbnail')) {
            if ($path = $this->convertToWebpSquare($game->thumbnail, 160)) {
                $updates['thumbnail'] = $path;
            }
        }

        if ($this->iconRulesChanged($game)) {
            $iconRules = $game->icon_rules ?? [];
            $changed = false;

            foreach ($iconRules as &$rule) {
                if (! empty($rule['icon']) && ! Str::endsWith($rule['icon'], '.webp')) {
                    if ($path = $this->convertToWebpSquare($rule['icon'], 128)) {
                        $rule['icon'] = $path;
                        $changed = true;
                    }
                }
            }
            unset($rule);

            if ($changed) {
                $updates['icon_rules'] = $iconRules;
            }
        }

        if (! empty($updates)) {
            $game->updateQuietly($updates);
        }
    }

    private function fieldChanged(Game $game, string $field): bool
    {
        $value = $game->$field;

        if (! $value || Str::endsWith($value, '.webp')) {
            return false;
        }

        return $game->wasRecentlyCreated || $game->wasChanged($field);
    }

    private function iconRulesChanged(Game $game): bool
    {
        return $game->wasRecentlyCreated || $game->wasChanged('icon_rules');
    }

    /**
     * Scale down proportionally to max width, convert to WebP.
     * Also generates a small (-sm.webp) variant for srcset.
     */
    private function convertToWebp(?string $relativePath, int $maxWidth): ?string
    {
        if (! $relativePath || Str::endsWith($relativePath, '.webp')) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($relativePath)) {
            return null;
        }

        $newRelativePath = Str::beforeLast($relativePath, '.') . '.webp';

        Image::read($disk->path($relativePath))
            ->scaleDown(width: $maxWidth)
            ->toWebp(80)
            ->save($disk->path($newRelativePath));

        $disk->delete($relativePath);

        $this->generateSmallVariant($disk, $newRelativePath);

        return $newRelativePath;
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

    /**
     * Cover crop to square, convert to WebP.
     */
    private function convertToWebpSquare(?string $relativePath, int $size): ?string
    {
        if (! $relativePath || Str::endsWith($relativePath, '.webp')) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($relativePath)) {
            return null;
        }

        $newRelativePath = Str::beforeLast($relativePath, '.') . '.webp';

        Image::read($disk->path($relativePath))
            ->cover($size, $size)
            ->toWebp(80)
            ->save($disk->path($newRelativePath));

        $disk->delete($relativePath);

        return $newRelativePath;
    }
}
