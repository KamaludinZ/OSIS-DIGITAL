<?php

namespace App\Filament\Resources\Voters\Schemas;

use App\Enums\JenisPemilih;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class VoterForm
{
    public static array $labelVerifikasi = ['terverifikasi' => 'Terverifikasi', 'menunggu' => 'Menunggu', 'ditolak' => 'Ditolak'];

    public static function configure(Schema $schema): Schema
    {
        $siswa = fn (Get $get) => ($get('jenis') instanceof JenisPemilih ? $get('jenis')->value : $get('jenis')) !== 'gtk';

        return $schema
            ->components([
                ToggleButtons::make('jenis')->label('Jenis pemilih')
                    ->options(['siswa' => 'Siswa', 'gtk' => 'GTK'])
                    ->default('siswa')->inline()->required()->live(),
                TextInput::make('nomor_induk')
                    ->label(fn (Get $get) => $siswa($get) ? 'NISN' : 'NIK')
                    ->required()
                    ->regex(fn (Get $get) => $siswa($get) ? '/^\d{10}$/' : '/^\d{16}$/')
                    ->validationMessages(['regex' => 'NISN harus 10 angka, NIK harus 16 angka.'])
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('jenis', $siswa($get) ? 'siswa' : 'gtk')
                        // Unik di periode aktif saja: siswa yang sama boleh terdaftar lagi tahun berikutnya.
                        ->where('periode_id', \App\Models\OsisPeriod::aktif()?->id)),
                TextInput::make('nama')->label('Nama lengkap')->required()->maxLength(150),
                TextInput::make('kelas')->label('Kelas')->placeholder('Contoh: VIII-B')->maxLength(20)->visible($siswa),
                TextInput::make('jabatan')->label('Jabatan')->placeholder('Contoh: Guru Matematika')->maxLength(100)->hidden($siswa),
                DatePicker::make('tanggal_lahir')->label('Tanggal lahir')->required()->native(false)->displayFormat('j F Y')->maxDate(now()),
                ToggleButtons::make('verifikasi')->label('Verifikasi')
                    ->options(self::$labelVerifikasi)
                    ->colors(['terverifikasi' => 'success', 'menunggu' => 'warning', 'ditolak' => 'danger'])
                    ->default('terverifikasi')->inline()->required(),
            ]);
    }
}
