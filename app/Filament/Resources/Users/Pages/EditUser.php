<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\Peran;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->hidden(fn () => $this->record->is(auth()->user()))];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return self::rapikan($data + ['role' => $this->record->role->value]);
    }

    /** Hanya akun kandidat yang terhubung ke kandidat; email diseragamkan huruf kecil. */
    public static function rapikan(array $data): array
    {
        $peran = $data['role'] instanceof Peran ? $data['role'] : Peran::from($data['role']);
        $data['email'] = strtolower(trim($data['email']));

        if ($peran !== Peran::Kandidat) {
            $data['candidate_id'] = null;
        }

        return $data;
    }
}
