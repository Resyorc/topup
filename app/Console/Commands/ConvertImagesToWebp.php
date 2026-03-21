<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\Game;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class ConvertImagesToWebp extends Command
{
    protected $signature = 'images:convert-webp';

    protected $description = 'Konversi semua gambar lama (PNG/JPG) ke format WebP';

    public function handle(): int
    {
        $disk = Storage::disk('public');

        // ── Games ───────────────────────────────────────────────────────────
        $this->info('Memproses gambar Game...');
        $games = Game::all();

        $bar = $this->output->createProgressBar($games->count());
        $bar->start();

        foreach ($games as $game) {
            $updates = [];

            if ($game->image) {
                if (! Str::endsWith($game->image, '.webp')) {
                    if ($path = $this->convertScaleDown($disk, $game->image, 400)) {
                        $updates['image'] = $path;
                    }
                }
                $imageForSmall = $updates['image'] ?? $game->image;
                if (Str::endsWith($imageForSmall, '.webp')) {
                    $this->generateSmall($disk, $imageForSmall);
                }
            }

            if ($game->thumbnail && ! Str::endsWith($game->thumbnail, '.webp')) {
                if ($path = $this->convertSquare($disk, $game->thumbnail, 160)) {
                    $updates['thumbnail'] = $path;
                }
            }

            $iconRules = $game->icon_rules ?? [];
            $iconChanged = false;
            foreach ($iconRules as &$rule) {
                if (! empty($rule['icon']) && ! Str::endsWith($rule['icon'], '.webp')) {
                    if ($path = $this->convertSquare($disk, $rule['icon'], 128)) {
                        $rule['icon'] = $path;
                        $iconChanged = true;
                    }
                }
            }
            unset($rule);

            if ($iconChanged) {
                $updates['icon_rules'] = $iconRules;
            }

            if (! empty($updates)) {
                $game->updateQuietly($updates);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // ── Banners ─────────────────────────────────────────────────────────
        $this->info('Memproses gambar Banner...');
        $banners = Banner::whereNotNull('image')->get();

        $bar = $this->output->createProgressBar($banners->count());
        $bar->start();

        foreach ($banners as $banner) {
            if (! Str::endsWith($banner->image, '.webp')) {
                if ($path = $this->convertScaleDown($disk, $banner->image, 1200)) {
                    $banner->updateQuietly(['image' => $path]);
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info('Selesai!');

        return self::SUCCESS;
    }

    private function convertScaleDown($disk, string $relativePath, int $maxWidth): ?string
    {
        if (! $disk->exists($relativePath)) {
            $this->warn("  File tidak ditemukan: {$relativePath}");
            return null;
        }

        $newPath = Str::beforeLast($relativePath, '.') . '.webp';

        try {
            Image::read($disk->path($relativePath))
                ->scaleDown(width: $maxWidth)
                ->toWebp(80)
                ->save($disk->path($newPath));

            $disk->delete($relativePath);
        } catch (\Throwable $e) {
            $this->warn("  Gagal konversi {$relativePath}: {$e->getMessage()}");
            return null;
        }

        return $newPath;
    }

    private function generateSmall($disk, string $relativePath, int $smallWidth = 188): void
    {
        $smPath = Str::beforeLast($relativePath, '.webp') . '-sm.webp';

        if ($disk->exists($smPath) || ! $disk->exists($relativePath)) {
            return;
        }

        try {
            Image::read($disk->path($relativePath))
                ->scaleDown(width: $smallWidth)
                ->toWebp(80)
                ->save($disk->path($smPath));
        } catch (\Throwable $e) {
            $this->warn("  Gagal generate small: {$relativePath}: {$e->getMessage()}");
        }
    }

    private function convertSquare($disk, string $relativePath, int $size): ?string
    {
        if (! $disk->exists($relativePath)) {
            $this->warn("  File tidak ditemukan: {$relativePath}");
            return null;
        }

        $newPath = Str::beforeLast($relativePath, '.') . '.webp';

        try {
            Image::read($disk->path($relativePath))
                ->cover($size, $size)
                ->toWebp(80)
                ->save($disk->path($newPath));

            $disk->delete($relativePath);
        } catch (\Throwable $e) {
            $this->warn("  Gagal konversi {$relativePath}: {$e->getMessage()}");
            return null;
        }

        return $newPath;
    }
}
