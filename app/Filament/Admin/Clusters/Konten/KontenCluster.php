<?php

namespace App\Filament\Admin\Clusters\Konten;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class KontenCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Konten';

    public static function getNavigationGroup(): ?string
    {
        return __('filament/navigation.groups.shop');
    }
}
