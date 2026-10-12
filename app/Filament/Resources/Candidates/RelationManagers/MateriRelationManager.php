<?php

namespace App\Filament\Resources\Candidates\RelationManagers;

use App\Enums\TipeMateri;
use App\Models\CampaignMaterial;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Materi kampanye kandidat: poster (unggah), video (tautan YouTube), janji kerja (teks). */
class MateriRelationManager extends RelationManager
{
    protected static string $relationship = 'materials';

    protected static ?string $title = 'Materi kampanye';

    protected static ?string $modelLabel = 'materi';

    public const LABEL = ['poster' => 'Poster', 'video' => 'Video', 'janji' => 'Janji kerja'];

    public function form(Schema $schema): Schema
    {
        $tipe = fn (Get $get) => $get('tipe') instanceof TipeMateri ? $get('tipe')->value : $get('tipe');

        return $schema->components([
            Radio::make('tipe')->label('Jenis materi')->options(self::LABEL)->default('janji')->inline()->required()->live()->columnSpanFull(),
            FileUpload::make('url_konten')->label('Gambar poster')
                ->helperText('Potret 3:4 paling pas. Maks. 3 MB.')
                ->image()->disk('public')->directory('kandidat/materi')->visibility('public')->maxSize(3072)
                ->visible(fn (Get $get) => $tipe($get) === 'poster')->required(fn (Get $get) => $tipe($get) === 'poster')
                ->columnSpanFull(),
            TextInput::make('url_konten')->label('Tautan video')->url()->placeholder('https://www.youtube.com/watch?v=…')
                ->helperText('Tautan YouTube (biasa atau Shorts) akan diputar langsung di profil kandidat.')
                ->visible(fn (Get $get) => $tipe($get) === 'video')->required(fn (Get $get) => $tipe($get) === 'video')
                ->columnSpanFull(),
            Textarea::make('deskripsi')->label(fn (Get $get) => $tipe($get) === 'janji' ? 'Janji kerja' : 'Keterangan')
                ->rows(3)->maxLength(500)->required(fn (Get $get) => $tipe($get) === 'janji')->columnSpanFull(),
            TextInput::make('urutan')->label('Urutan')->integer()->minValue(0)->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('urutan')
            ->reorderable('urutan')
            ->columns([
                TextColumn::make('tipe')->label('Jenis')->badge()
                    ->formatStateUsing(fn (TipeMateri $state) => self::LABEL[$state->value])
                    ->color(fn (TipeMateri $state) => ['poster' => 'info', 'video' => 'warning', 'janji' => 'success'][$state->value]),
                TextColumn::make('deskripsi')->label('Isi')->wrap()->limit(120)->placeholder('—'),
                TextColumn::make('url_konten')->label('Berkas / tautan')->limit(40)->placeholder('—')
                    ->url(fn (CampaignMaterial $r) => $r->urlPublik(), shouldOpenInNewTab: true),
            ])
            ->headerActions([CreateAction::make()->label('Tambah materi')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
