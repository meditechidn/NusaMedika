<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sinkronkan status pemesanan lama dengan realisasi penerimaannya.
     *
     * Sebelum penerimaan bertahap diterapkan, setiap penyimpanan penerimaan
     * langsung menandai PO "Diterima" (2) walau jumlahnya belum lengkap.
     * Migrasi ini menurunkan PO seperti itu ke "Proses Sebagian" (4) dan
     * memastikan PO yang sudah lengkap tetap "Diterima" (2).
     */
    public function up(): void
    {
        $pemesanans = DB::table('pemesanan')
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->whereIn('status_pemesanan', [1, 2, 4])
            ->get(['pemesanan_id', 'status_pemesanan']);

        foreach ($pemesanans as $pemesanan) {
            $terima = DB::table('penerimaan_detail as pd')
                ->join('penerimaan as p', 'pd.penerimaan_id', '=', 'p.penerimaan_id')
                ->where('p.pemesanan_id', $pemesanan->pemesanan_id)
                ->where(function ($q) {
                    $q->whereNull('p.status_batal')->orWhere('p.status_batal', 0);
                })
                ->where(function ($q) {
                    $q->whereNull('pd.status_batal')->orWhere('pd.status_batal', 0);
                })
                ->sum('pd.jumlah_terima');

            if ((float) $terima <= 0) {
                continue;
            }

            $pesan = DB::table('pemesanan_detail')
                ->where('pemesanan_id', $pemesanan->pemesanan_id)
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->sum('jumlah_pesan');

            $status = (float) $terima >= (float) $pesan ? 2 : 4;

            if ((int) $pemesanan->status_pemesanan === $status) {
                continue;
            }

            DB::table('pemesanan')
                ->where('pemesanan_id', $pemesanan->pemesanan_id)
                ->update([
                    'status_pemesanan' => $status,
                    'mod_time' => now(),
                    'mod_user_id' => 1,
                ]);
        }
    }

    public function down(): void
    {
        // Perbaikan data satu arah: status lama (selalu 2) tidak bisa direkonstruksi.
    }
};
