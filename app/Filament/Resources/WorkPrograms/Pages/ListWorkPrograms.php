<?php

namespace App\Filament\Resources\WorkPrograms\Pages;

use App\Filament\Resources\WorkPrograms\WorkProgramResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWorkPrograms extends ListRecords
{
    protected static string $resource = WorkProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
