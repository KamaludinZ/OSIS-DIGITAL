<?php

namespace App\Filament\Resources\WorkPrograms\Schemas;

use App\Enums\StatusProgramKerja;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;

class WorkProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')->label('Nama program')->required()->maxLength(150),
                Select::make('divisi_id')->label('Divisi')->relationship('divisi', 'nama')->searchable()->preload()->required(),
                Textarea::make('deskripsi')->label('Deskripsi')->rows(3)->maxLength(1000)->columnSpanFull(),
                ToggleButtons::make('status')
                    ->label('Status')
                    ->options(self::statusLabel())
                    ->colors(['rencana' => 'gray', 'jalan' => 'warning', 'selesai' => 'success'])
                    ->default(StatusProgramKerja::Rencana->value)
                    ->inline()
                    ->required(),
                TextInput::make('capaian')->label('Capaian')->placeholder('Contoh: 6 sesi, 540 peserta')->maxLength(150),
                Toggle::make('unggulan')->label('Tampilkan sebagai sorotan di Beranda')
                    ->helperText('Maksimal '.\App\Models\WorkProgram::BATAS_UNGGULAN.' program unggulan sekaligus.')
                    ->rules([fn (?\App\Models\WorkProgram $record) => function (string $attribute, $value, \Closure $fail) use ($record) {
                        if ($value && ! $record?->unggulan && \App\Models\WorkProgram::kuotaUnggulanTersisa($record?->id) <= 0) {
                            $fail('Sudah ada '.\App\Models\WorkProgram::BATAS_UNGGULAN.' program unggulan. Matikan sorotan program lain dulu.');
                        }
                    }])
                    ->columnSpanFull(),
            ]);
    }

    public static function statusLabel(): array
    {
        return ['rencana' => 'Direncanakan', 'jalan' => 'Sedang berjalan', 'selesai' => 'Selesai'];
    }
}
