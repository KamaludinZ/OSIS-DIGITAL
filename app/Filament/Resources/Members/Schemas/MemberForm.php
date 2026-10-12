<?php

namespace App\Filament\Resources\Members\Schemas;

use App\Models\Member;
use App\Models\Voter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('voter_id')
                    ->label('Ambil dari data siswa (opsional)')
                    ->helperText('Cari nama/NISN siswa yang sudah diimpor panitia; nama & kelas terisi otomatis.')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => Voter::where('jenis', 'siswa')
                        ->where(fn ($q) => $q->where('nama', 'like', "%{$search}%")->orWhere('nomor_induk', 'like', "%{$search}%"))
                        ->limit(20)->get()->mapWithKeys(fn (Voter $v) => [$v->id => $v->nama.' · '.$v->kelas])->all())
                    ->getOptionLabelUsing(fn ($value) => ($v = Voter::find($value)) ? $v->nama.' · '.$v->kelas : null)
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if ($v = Voter::find($state)) {
                            $set('nama', $v->nama);
                            $set('kelas', $v->kelas);
                        }
                    })
                    ->columnSpanFull(),
                TextInput::make('nama')->label('Nama')->required()->maxLength(150),
                TextInput::make('kelas')->label('Kelas')->placeholder('Contoh: VIII-B')->maxLength(20),
                Select::make('divisi_id')->label('Divisi')
                    ->relationship('divisi', 'nama')
                    ->placeholder('Pengurus inti')
                    ->helperText('Kosongkan untuk pengurus inti (ketua, wakil, sekretaris, bendahara).')
                    ->searchable()->preload()->live(),
                TextInput::make('jabatan')->label('Jabatan')->required()->maxLength(100)
                    ->datalist(fn (Get $get) => blank($get('divisi_id')) ? Member::JABATAN_INTI : ['Koordinator', 'Anggota'])
                    ->default('Anggota'),
                TextInput::make('periode')->label('Periode')->required()
                    ->regex('/^\\d{4}\\/\\d{4}$/')->validationMessages(['regex' => 'Format periode: 2026/2027.'])
                    ->default(fn () => Member::periodeAktif()),
            ]);
    }
}
