<?php

namespace App\Filament\Pages;

use App\Models\Division;
use App\Models\Event;
use App\Models\Member;
use App\Models\WorkProgram;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/** Halaman utama Ruang OSIM: ringkasan organisasi untuk pengurus. */
class Ringkasan extends Page
{
    protected string $view = 'filament.pages.ringkasan';

    protected static string $routePath = '/';

    protected static ?string $title = 'Ringkasan Organisasi';

    protected static ?string $navigationLabel = 'Ringkasan';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?int $navigationSort = -10;

    public function getSubheading(): ?string
    {
        return 'Kepengurusan OSIM periode '.Member::periodeAktif();
    }

    protected function getViewData(): array
    {
        $periode = Member::periodeAktif();
        $urutInti = array_flip(Member::JABATAN_INTI);

        $inti = Member::query()->periode($periode)->inti()->get()
            ->sortBy(fn (Member $m) => [$urutInti[$m->jabatan] ?? 99, $m->urutan ?? 0])
            ->map(fn (Member $m) => ['jabatan' => $m->jabatan, 'nama' => $m->nama, 'kelas' => $m->kelas ?? '—'])
            ->values()->all();

        $divisi = Division::query()
            ->withCount(['members' => fn ($q) => $q->periode($periode), 'workPrograms'])
            ->with(['members' => fn ($q) => $q->periode($periode)->where('jabatan', 'Koordinator')])
            ->orderBy('nama')->get()
            ->map(fn (Division $d) => [
                'nama' => $d->nama,
                'koordinator' => $d->members->first()?->nama ?? 'Belum ditentukan',
                'anggota' => $d->members_count,
                'program' => $d->work_programs_count,
            ])->all();

        $program = WorkProgram::query()->with('divisi')->orderBy('nama')->get()
            ->map(fn (WorkProgram $p) => ['nama' => $p->nama, 'divisi' => $p->divisi?->nama ?? 'Pengurus OSIM', 'status' => $p->status->value]);

        $jadwal = Event::query()->mendatang()->with('divisi')->limit(5)->get()
            ->map(fn (Event $e) => ['nama' => $e->nama, 'tanggal' => $e->tanggal_mulai->toDateString(), 'tempat' => $e->tempat ?? '—', 'divisi' => $e->divisi?->nama ?? 'Pengurus inti'])
            ->all();

        return [
            'inti' => $inti,
            'divisi' => $divisi,
            'program' => $program->groupBy('status'),
            'jadwal' => $jadwal,
            'angka' => [
                ['Anggota pengurus', Member::query()->periode($periode)->count()],
                ['Divisi', count($divisi)],
                ['Program berjalan', $program->where('status', 'jalan')->count()],
                ['Kegiatan terjadwal', Event::query()->mendatang()->count()],
            ],
        ];
    }
}
