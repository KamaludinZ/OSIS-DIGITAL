<?php

namespace App\Filament\Resources\WorkPrograms\Pages;

use App\Filament\Resources\WorkPrograms\WorkProgramResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWorkProgram extends EditRecord
{
    protected static string $resource = WorkProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
