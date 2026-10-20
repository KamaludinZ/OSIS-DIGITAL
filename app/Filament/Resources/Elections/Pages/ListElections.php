<?php

namespace App\Filament\Resources\Elections\Pages;

use App\Filament\Resources\Elections\ElectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ListElections extends ListRecords
{
    protected static string $resource = ElectionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    /** Kartu sakelar mode pemilihan di atas daftar periode pemilihan. */
    public function content(Schema $schema): Schema
    {
        $isi = parent::content($schema);

        return $isi->components([View::make('filament.mode-pemilihan'), ...$isi->getComponents(withHidden: true)]);
    }
}
