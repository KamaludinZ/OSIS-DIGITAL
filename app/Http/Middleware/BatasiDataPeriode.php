<?php

namespace App\Http\Middleware;

use App\Enums\StatusPeriode;
use App\Models\OsisPeriod;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Data ber-periode_id hanya bisa dibuka lewat alamat langsung bila milik periode aktif.
 * - Periode arsip: hanya-baca (GET) bila rute mengizinkan arsip, selain itu 404; perubahan selalu 403.
 * - Periode yang belum aktif / tidak cocok: 404 (seolah tidak ada).
 *
 * Pemakaian di rute: ->middleware('periode:'.Candidate::class.',id') atau 'periode:Model,param,arsip'.
 * Di Ruang OSIM (Filament), model {record} dibaca dari resource halaman tanpa parameter.
 */
class BatasiDataPeriode
{
    public function handle(Request $request, Closure $next, ?string $model = null, string $parameter = 'id', ?string $izinkanArsip = null): Response
    {
        $route = $request->route();
        $model ??= $this->modelResourceFilament($route?->getControllerClass());
        $parameter = $model && $route?->hasParameter($parameter) ? $parameter : 'record';

        if (! $model || ! $route?->hasParameter($parameter) || ! $this->berPeriode($model)) {
            return $next($request);
        }

        $nilai = $route->parameter($parameter);
        $idPeriode = $nilai instanceof Model
            ? $nilai->getAttribute('periode_id')
            : $model::query()->withoutGlobalScopes()->whereKey($nilai)->value('periode_id');

        if ($idPeriode === null || (int) $idPeriode === OsisPeriod::aktif()?->id) {
            return $next($request);
        }

        $arsip = OsisPeriod::query()->whereKey($idPeriode)->where('status', StatusPeriode::Arsip)->exists();
        $baca = $request->isMethodSafe();

        abort_if($arsip && ! $baca, 403, 'Data periode arsip hanya bisa dilihat.');
        abort_unless($arsip && $baca && $izinkanArsip === 'arsip', 404);

        return $next($request);
    }

    /** @param  class-string|null  $kelas */
    private function modelResourceFilament(?string $kelas): ?string
    {
        return $kelas && method_exists($kelas, 'getResource') ? $kelas::getResource()::getModel() : null;
    }

    /** @param  class-string<Model>  $model */
    private function berPeriode(string $model): bool
    {
        static $kolom = [];

        return $kolom[$model] ??= Schema::hasColumn((new $model)->getTable(), 'periode_id');
    }
}
