<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return self::rapikan($data);
    }

    /** Tayang tanpa jadwal = sekarang; draf tidak menyimpan jadwal. */
    public static function rapikan(array $data): array
    {
        $data['dipublikasikan'] = (bool) ($data['dipublikasikan'] ?? false);

        if (! $data['dipublikasikan']) {
            $data['dipublikasikan_pada'] = null;
        } elseif (blank($data['dipublikasikan_pada'] ?? null)) {
            $data['dipublikasikan_pada'] = now();
        }

        $data['penulis_label'] = filled($data['penulis_label'] ?? null) ? $data['penulis_label'] : null;

        return $data;
    }
}
