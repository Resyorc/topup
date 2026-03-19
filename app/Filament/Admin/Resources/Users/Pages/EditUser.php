<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * coin_balance tidak ada di $fillable (cegah mass assignment).
     * Update dilakukan secara eksplisit di sini khusus untuk admin panel.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $coinBalance = isset($data['coin_balance']) ? (int) $data['coin_balance'] : null;
        unset($data['coin_balance']);

        $record->fill($data)->save();

        if ($coinBalance !== null) {
            $record->coin_balance = $coinBalance;
            $record->saveQuietly();
        }

        return $record;
    }
}
