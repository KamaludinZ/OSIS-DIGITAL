<?php

namespace App\Filament\Resources\Voters\Pages;

use App\Filament\Resources\Voters\VoterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVoter extends EditRecord
{
    protected static string $resource = VoterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
