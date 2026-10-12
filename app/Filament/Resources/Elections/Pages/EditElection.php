<?php

namespace App\Filament\Resources\Elections\Pages;

use App\Enums\FormatKandidat;
use App\Filament\Resources\Elections\ElectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditElection extends EditRecord
{
    protected static string $resource = ElectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn () => $this->record->votes()->exists())
                ->modalDescription('Semua kandidat, materi kampanye, dan tanya jawab periode ini ikut terhapus.'),
        ];
    }

    protected function afterSave(): void
    {
        // Format "ketua saja": data wakil kandidat periode ini dikosongkan.
        if ($this->record->format_kandidat === FormatKandidat::Ketua) {
            $this->record->candidates()->update(['nama_wakil' => null, 'kelas_wakil' => null]);
        }
    }
}
