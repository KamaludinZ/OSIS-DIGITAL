<?php

namespace App\Filament\Resources\Candidates\Pages;

use App\Enums\FormatKandidat;
use App\Filament\Resources\Candidates\CandidateResource;
use App\Models\Election;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCandidate extends EditRecord
{
    protected static string $resource = CandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->hidden(fn () => $this->record->votes()->exists())];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return self::sesuaikanFormat($data + ['election_id' => $this->record->election_id]);
    }

    /** Pemilihan berformat "ketua saja" tidak menyimpan data wakil. */
    public static function sesuaikanFormat(array $data): array
    {
        $data['misi'] = array_values(array_filter(array_map('trim', $data['misi'] ?? []), 'strlen'));

        if (Election::find($data['election_id'] ?? null)?->format_kandidat === FormatKandidat::Ketua) {
            $data['nama_wakil'] = null;
            $data['kelas_wakil'] = null;
        }

        return $data;
    }
}
