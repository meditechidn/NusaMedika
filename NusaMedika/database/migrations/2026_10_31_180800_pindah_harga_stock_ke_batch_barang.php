<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pindahkan harga_beli/harga_jual dari tabel `stock` ke tabel `batch_barang`
 * dan hubungkan keduanya lewat kolom `stock.id_batch`.
 *
 * Sebelumnya `stock` menyimpan harga per (barang, bagian, batch); sekarang
 * harga disimpan sekali per (barang, batch) di `batch_barang`, dan `stock`
 * hanya menyimpan saldo per lokasi (bagian) yang mereferensi `batch_barang`.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Pada skema baru, stock tidak lagi punya kolom harga_beli/harga_jual,
        // jadi tidak ada data yang perlu dipindahkan (fresh install).
        if (! Schema::hasColumn('stock', 'harga_beli')) {
            // Tetap pastikan id_batch NOT NULL & FK sudah ada (di-handle create_stock_table).
            return;
        }

        // 1. Isi batch_barang dari data stock yang ada (per barang + no_batch).
        DB::statement('
            INSERT INTO batch_barang (barang_id, no_batch, harga_beli, harga_jual, input_time, mod_time, status_batal)
            SELECT DISTINCT ON (barang_id, no_batch)
                   barang_id,
                   no_batch,
                   COALESCE(harga_beli, 0),
                   COALESCE(harga_jual, 0),
                   NOW(),
                   NOW(),
                   0
            FROM stock
            WHERE NOT EXISTS (
                SELECT 1 FROM batch_barang b
                WHERE b.barang_id = stock.barang_id AND b.no_batch = stock.no_batch
            )
            ORDER BY barang_id, no_batch, stock_id
        ');

        // 2. Set stock.id_batch sesuai batch_barang yang cocok.
        DB::statement('
            UPDATE stock
            SET id_batch = (
                SELECT b.id_batch FROM batch_barang b
                WHERE b.barang_id = stock.barang_id AND b.no_batch = stock.no_batch
                LIMIT 1
            )
            WHERE id_batch IS NULL
        ');

        // 3. Hapus unique index lama (yang memuat harga_jual).
        DB::statement('DROP INDEX IF EXISTS stock_barang_id_bagian_id_no_batch_harga_jual_unique');

        // 4. Hapus kolom harga dari stock (sudah ada di batch_barang).
        if (Schema::hasColumn('stock', 'harga_beli')) {
            Schema::table('stock', function ($table) {
                $table->dropColumn(['harga_beli', 'harga_jual']);
            });
        }

        // 5. id_batch wajib terisi dan mereferensi batch_barang.
        DB::statement('ALTER TABLE stock ALTER COLUMN id_batch SET NOT NULL');
        DB::statement('ALTER TABLE stock ADD CONSTRAINT stock_id_batch_foreign FOREIGN KEY (id_batch) REFERENCES batch_barang (id_batch) ON DELETE RESTRICT');

        // 6. Unique key baru: satu baris stok per (batch, bagian).
        DB::statement('CREATE UNIQUE INDEX stock_id_batch_bagian_id_unique ON stock (id_batch, bagian_id) WHERE (status_batal IS NULL) OR (status_batal = 0)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS stock_id_batch_bagian_id_unique');
        DB::statement('ALTER TABLE stock DROP CONSTRAINT IF EXISTS stock_id_batch_foreign');
        DB::statement('ALTER TABLE stock ALTER COLUMN id_batch DROP NOT NULL');

        Schema::table('stock', function ($table) {
            $table->decimal('harga_beli', 15)->nullable();
            $table->decimal('harga_jual', 15)->nullable();
        });

        DB::statement('
            UPDATE stock
            SET harga_beli = (SELECT b.harga_beli FROM batch_barang b WHERE b.id_batch = stock.id_batch),
                harga_jual = (SELECT b.harga_jual FROM batch_barang b WHERE b.id_batch = stock.id_batch)
        ');

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS stock_barang_id_bagian_id_no_batch_harga_jual_unique ON stock (barang_id, bagian_id, no_batch, harga_jual) WHERE (status_batal IS NULL) OR (status_batal = 0)');
    }
};
