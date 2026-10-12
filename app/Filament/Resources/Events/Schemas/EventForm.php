<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Models\Event;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        $bentrok = fn (Get $get, ?Event $record) => Event::bentrok($get('tanggal_mulai'), $get('tanggal_selesai'), $get('tempat'), $record?->id);

        return $schema
            ->components([
                TextInput::make('nama')->label('Nama kegiatan')->required()->maxLength(150)->columnSpanFull(),
                DatePicker::make('tanggal_mulai')->label('Tanggal mulai')->required()->native(false)->displayFormat('j F Y')->live(),
                DatePicker::make('tanggal_selesai')->label('Tanggal selesai')->required()->native(false)->displayFormat('j F Y')
                    ->afterOrEqual('tanggal_mulai')->live(),
                TextInput::make('tempat')->label('Tempat')->placeholder('Contoh: Aula Utama')->required()->maxLength(150)
                    ->datalist(fn () => Event::query()->distinct()->orderBy('tempat')->pluck('tempat')->all())
                    ->live(onBlur: true),
                Select::make('divisi_id')->label('Divisi penanggung jawab')->relationship('divisi', 'nama')->searchable()->preload(),

                // Peringatan bentrok: muncul langsung saat tanggal & tempat diisi.
                Callout::make(fn (Get $get, ?Event $record) => 'Bentrok dengan '.$bentrok($get, $record)->count().' kegiatan di tempat yang sama')
                    ->description(fn (Get $get, ?Event $record) => $bentrok($get, $record)
                        ->map(fn (Event $e) => $e->nama.' ('.self::rentang($e->tanggal_mulai, $e->tanggal_selesai).')')
                        ->implode(' · ').'. Pertimbangkan mengganti tanggal atau tempat.')
                    ->warning()
                    ->visible(fn (Get $get, ?Event $record) => $bentrok($get, $record)->isNotEmpty())
                    ->columnSpanFull(),

                // Server menolak jadwal bentrok kecuali panitia sengaja mengizinkan (mis. kegiatan gabungan).
                Checkbox::make('abaikan_bentrok')
                    ->label('Tetap simpan meski bentrok (kegiatan gabungan/sengaja di tempat yang sama)')
                    ->dehydrated(false)
                    ->visible(fn (Get $get, ?Event $record) => $bentrok($get, $record)->isNotEmpty())
                    ->rules([fn (Get $get, ?Event $record) => function (string $attribute, $value, \Closure $fail) use ($get, $record, $bentrok) {
                        if (! $value && $bentrok($get, $record)->isNotEmpty()) {
                            $fail('Jadwal bentrok dengan kegiatan lain di tempat yang sama. Ganti tanggal/tempat, atau centang untuk tetap menyimpan.');
                        }
                    }])
                    ->columnSpanFull(),

                Textarea::make('deskripsi')->label('Deskripsi')->rows(3)->maxLength(1000)->columnSpanFull(),
            ]);
    }

    public static function rentang(Carbon $mulai, Carbon $selesai): string
    {
        return $mulai->isSameDay($selesai)
            ? $mulai->translatedFormat('j M Y')
            : $mulai->translatedFormat('j M').' – '.$selesai->translatedFormat('j M Y');
    }
}
