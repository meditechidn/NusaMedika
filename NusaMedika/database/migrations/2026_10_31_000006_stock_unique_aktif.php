<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Unique key stock dibuat parsial (hanya baris aktif).
 *
 * Tabel `stock` menyimpan saldo berjalan per (barang, bagian, batch, harga jual).
 * Stock Opname perlu menghapus baris stok (soft delete) lalu transaksi
 * penerimaan berikutnya membuat baris baru untuk kombinasi yang sama — tanpa
 * index parsial, baris hasil soft delete tetap menempati unique key sehingga
 * insert baru gagal "duplicate key".
 */
return new class extends Migration
{
    private const INDEX = 'stock_barang_id_bagian_id_no_batch_harga_jual_unique';

    public function up(): void
    {
        // Unique key lama dibuat sebagai constraint (bukan index lepas) oleh
        // migration create_stock_table, jadi harus di-drop sebagai constraint.
        DB::statement('ALTER TABLE stock DROP CONSTRAINT IF EXISTS '.self::INDEX);
        DB::statement('DROP INDEX IF EXISTS '.self::INDEX);
        DB::statement('CREATE UNIQUE INDEX '.self::INDEX.'
            ON stock (barang_id, bagian_id, no_batch, harga_jual)
            WHERE status_batal IS NULL OR status_batal = 0');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS '.self::INDEX);
        DB::statement('ALTER TABLE stock ADD CONSTRAINT '.self::INDEX.'
            UNIQUE (barang_id, bagian_id, no_batch, harga_jual)');
    }
};
