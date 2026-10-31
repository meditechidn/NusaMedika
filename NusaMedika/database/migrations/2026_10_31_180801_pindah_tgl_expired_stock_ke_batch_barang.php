<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pindahkan tgl_expired dari tabel `stock` ke `batch_barang` (join via id_batch).
 * Kadaluwarsa adalah sifat batch, bukan lokasi penyimpanan.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom tgl_expired di batch_barang bila belum ada (fresh install sudah punya).
        if (! Schema::hasColumn('batch_barang', 'tgl_expired')) {
            Schema::table('batch_barang', function ($table) {
                $table->date('tgl_expired')->nullable()->after('harga_jual');
            });
        }

        // 2. Pada skema baru (fresh install) stock tidak punya tgl_expired: lewati migrasi data.
        if (! Schema::hasColumn('stock', 'tgl_expired')) {
            return;
        }

        // 3. Isi batch_barang.tgl_expired dari stock (per batch, satu nilai).
        DB::statement('
            UPDATE batch_barang b
            SET tgl_expired = s.tgl_expired
            FROM (
                SELECT DISTINCT ON (id_batch) id_batch, tgl_expired
                FROM stock
                WHERE tgl_expired IS NOT NULL
                ORDER BY id_batch, stock_id
            ) s
            WHERE s.id_batch = b.id_batch
        ');

        // 4. Hapus kolom tgl_expired dari stock.
        Schema::table('stock', function ($table) {
            $table->dropColumn('tgl_expired');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('stock', 'tgl_expired')) {
            Schema::table('stock', function ($table) {
                $table->date('tgl_expired')->nullable();
            });
        }

        DB::statement('
            UPDATE stock
            SET tgl_expired = (SELECT b.tgl_expired FROM batch_barang b WHERE b.id_batch = stock.id_batch)
        ');

        if (Schema::hasColumn('batch_barang', 'tgl_expired')) {
            Schema::table('batch_barang', function ($table) {
                $table->dropColumn('tgl_expired');
            });
        }
    }
};
