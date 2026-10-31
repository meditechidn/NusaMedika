<?php

namespace App\Helpers;

use App\Models\Tarif;
use App\Models\Tindakan;
use Illuminate\Support\Facades\DB;

/**
 * Pusat resolusi tarif billing. Semua harga yang dipakai BillingHelper
 * berasal dari sini supaya aturan fallback konsisten dan mudah diuji.
 *
 * Sumber tarif tindakan hanya tabel `tarif`:
 *   tarif kelas (tindakan_id + kelas_ruang_id) -> tarif default (kelas NULL) -> 0
 */
class TarifHelper
{
    /**
     * Tarif tindakan: baris kelas perawatan bila ada, fallback tarif default.
     *
     * @return array{tarif: float, jasa_sarana: float, jasa_medis: float, bhp: float, kelas_ruang_id: ?int}
     */
    public static function tindakan(int $tindakanId, ?int $kelasRuangId = null): array
    {
        $harga = static::baris($tindakanId, $kelasRuangId);

        return [
            'tarif' => (float) ($harga->tarif ?? 0),
            'jasa_sarana' => (float) ($harga->jasa_sarana ?? 0),
            'jasa_medis' => (float) ($harga->jasa_medis ?? 0),
            'bhp' => (float) ($harga->bhp ?? 0),
            'kelas_ruang_id' => $harga && $harga->kelas_ruang_id !== null ? (int) $harga->kelas_ruang_id : null,
        ];
    }

    private static function baris(int $tindakanId, ?int $kelasRuangId): ?Tarif
    {
        if ($kelasRuangId) {
            $harga = Tarif::aktif()
                ->where('tindakan_id', $tindakanId)
                ->where('kelas_ruang_id', $kelasRuangId)
                ->orderByDesc('tarif_id')
                ->first();

            if ($harga) {
                return $harga;
            }
        }

        return Tarif::aktif()
            ->where('tindakan_id', $tindakanId)
            ->whereNull('kelas_ruang_id')
            ->orderByDesc('tarif_id')
            ->first();
    }

    /**
     * Harga barang: harga batch terakhir di `stock` (harga_beli / harga_jual).
     *
     * @return array{harga_beli: float, harga_jual: float, sumber: string}
     */
    public static function barang(int $barangId, ?string $noBatch = null, ?int $bagianId = null): array
    {
        $stock = DB::table('stock')
            ->where('barang_id', $barangId)
            ->when($noBatch, fn ($q) => $q->where('no_batch', $noBatch))
            ->when($bagianId, fn ($q) => $q->where('bagian_id', $bagianId))
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->orderByDesc('stock_id')
            ->first();

        return [
            'harga_beli' => (float) ($stock->harga_beli ?? 0),
            'harga_jual' => (float) ($stock->harga_jual ?? 0),
            'sumber' => $stock ? 'stock' : 'nol',
        ];
    }

    public static function kamar(?int $kelasRuangId = null): float
    {
        return static::komponen('KAMAR', $kelasRuangId);
    }

    public static function administrasi(?int $kelasRuangId = null): float
    {
        return static::komponen('ADMINISTRASI', $kelasRuangId);
    }

    public static function visite(?int $kelasRuangId = null): float
    {
        return static::komponen('VISITE', $kelasRuangId);
    }

    public static function komponen(string $kode, ?int $kelasRuangId = null): float
    {
        $tindakanId = static::tindakanIdByKode($kode);
        if (! $tindakanId) {
            return 0;
        }

        return static::tindakan($tindakanId, $kelasRuangId)['tarif'];
    }

    private static function tindakanIdByKode(string $kode): ?int
    {
        $id = Tindakan::aktif()
            ->where('kode_tindakan', $kode)
            ->value('tindakan_id');

        return $id ? (int) $id : null;
    }
}
