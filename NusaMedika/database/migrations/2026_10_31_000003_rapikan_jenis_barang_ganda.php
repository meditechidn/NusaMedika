<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rapikan Master Jenis Barang yang ganda (mis. "Obat" tercatat dua kali).
     *
     * - Baris aktif dengan nama sama (tanpa peduli besar/kecil & spasi tepi)
     *   digabung ke baris ber-id terkecil; `barang.jenis_barang_id` dipindahkan
     *   ke baris kanonik lalu duplikatnya di-soft-delete.
     * - Index unik parsial ditambahkan supaya nama jenis aktif tidak bisa ganda
     *   lagi (baris yang sudah dibatalkan tidak menghalangi pemakaian nama lama).
     */
    public function up(): void
    {
        $baris = DB::table('barang_jenis')
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->orderBy('barang_jenis_id')
            ->get(['barang_jenis_id', 'nama_jenis_barang']);

        $kanonik = [];

        foreach ($baris as $row) {
            $kunci = mb_strtolower(trim((string) $row->nama_jenis_barang));

            if (! isset($kanonik[$kunci])) {
                $kanonik[$kunci] = (int) $row->barang_jenis_id;

                continue;
            }

            $duplikatId = (int) $row->barang_jenis_id;

            DB::table('barang')
                ->where('jenis_barang_id', $duplikatId)
                ->update(['jenis_barang_id' => $kanonik[$kunci]]);

            DB::table('barang_jenis')
                ->where('barang_jenis_id', $duplikatId)
                ->update([
                    'status_batal' => 1,
                    'mod_time' => now(),
                    'mod_user_id' => 1,
                ]);
        }

        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS barang_jenis_nama_unik '
            .'ON barang_jenis (LOWER(BTRIM(nama_jenis_barang))) '
            .'WHERE status_batal IS NULL OR status_batal = 0'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS barang_jenis_nama_unik');
    }
};
