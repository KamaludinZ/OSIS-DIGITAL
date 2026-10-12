<?php

namespace App\Filament\Resources\Divisions\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DivisionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->label('Nama divisi')
                    ->placeholder('Contoh: Divisi Keagamaan')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true),
                Textarea::make('deskripsi')
                    ->label('Tugas & lingkup')
                    ->rows(3)
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }
}
