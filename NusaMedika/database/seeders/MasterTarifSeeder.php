<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterTarifSeeder extends Seeder
{
    /**
     * Baseline Master Tarif (tabel `tarif`). Satu tarif per tindakan per
     * kelas perawatan + satu baris tarif default (kelas_ruang_id NULL) sebagai
     * fallback ketika kelas spesifik belum diisi.
     *
     * Dipisah dari TindakanSeeder karena tarif kini dikelola di menu Master Tarif.
     */
    public function run(): void
    {
        $now = now();

        // Kelas ruang 1..5 = Kelas 1 / Kelas 2 / Kelas 3 / VIP / VVIP.
        $tarifPerKelas = [
            1 => 75000,
            2 => 65000,
            3 => 50000,
            4 => 100000,
            5 => 120000,
        ];

        $tindakan = DB::table('tindakan')
            ->join('kategori_tindakan', 'kategori_tindakan.kategori_tindakan_id', '=', 'tindakan.kategori_tindakan_id')
            ->where(function ($q) {
                $q->where('tindakan.status_batal', '!=', 1)->orWhereNull('tindakan.status_batal');
            })
            // Tarif kategori billing (administrasi/kamar/makanan/visite) dikelola
            // di BillingSeeder dan Master Tarif (Administrator → Manajemen Master), bukan di baseline ini.
            ->whereIn('kategori_tindakan.nama_kategori_tindakan', ['Laboratorium', 'Radiologi'])
            ->get(['tindakan.tindakan_id', 'kategori_tindakan.nama_kategori_tindakan']);

        foreach ($tindakan as $t) {
            $isLab = $t->nama_kategori_tindakan === 'Laboratorium';
            $base = $isLab ? 50000 : 100000;

            // Baris default (fallback bila kelas spesifik belum diisi).
            DB::table('tarif')->updateOrInsert(
                ['tindakan_id' => $t->tindakan_id, 'kelas_ruang_id' => null],
                [
                    'tarif' => $base,
                    'input_time' => $now,
                    'input_user_id' => 1,
                    'status_batal' => 0,
                ]
            );

            foreach ($tarifPerKelas as $kelasId => $tarif) {
                $kelasAda = DB::table('kelas_ruang')
                    ->where('kelas_ruang_id', $kelasId)
                    ->where(function ($q) {
                        $q->where('status_batal', '!=', 1)->orWhereNull('status_batal');
                    })
                    ->exists();

                if (! $kelasAda) {
                    continue;
                }

                DB::table('tarif')->updateOrInsert(
                    ['tindakan_id' => $t->tindakan_id, 'kelas_ruang_id' => $kelasId],
                    [
                        'tarif' => $tarif,
                        'input_time' => $now,
                        'input_user_id' => 1,
                        'status_batal' => 0,
                    ]
                );
            }
        }
    }
}
