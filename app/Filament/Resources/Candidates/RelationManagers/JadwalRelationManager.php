<?php

namespace App\Filament\Resources\Candidates\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

/** Jadwal kampanye kandidat; satu tempat tidak bisa dipakai dua kampanye di tanggal & jam yang sama. */
class JadwalRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    protected static ?string $title = 'Jadwal kampanye';

    protected static ?string $modelLabel = 'jadwal';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('tanggal')->label('Tanggal')->required(),
            TimePicker::make('waktu')->label('Jam')->seconds(false)->required(),
            TextInput::make('tempat')->label('Tempat')->placeholder('Contoh: Aula lantai 2')->required()->maxLength(100)
                ->unique('campaign_schedules', 'tempat', ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule
                    ->where(fn ($q) => $q->whereDate('tanggal', (string) $get('tanggal'))
                        ->whereTime('waktu', substr((string) $get('waktu'), 0, 5).':00')))
                ->validationMessages(['unique' => 'Tempat ini sudah dipakai kampanye lain pada tanggal dan jam yang sama.'])
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal')
            ->columns([
                TextColumn::make('tanggal')->label('Tanggal')->date('l, j F Y')->sortable(),
                TextColumn::make('waktu')->label('Jam')->time('H:i'),
                TextColumn::make('tempat')->label('Tempat'),
            ])
            ->headerActions([CreateAction::make()->label('Tambah jadwal')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
