<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Hapus tabel yang tidak dipakai aplikasi:
 * - `barang_harga`  : harga per penjamin/periode untuk barang; tarif barang
 *                     kini murni dari `stock` (harga_beli/harga_jual batch).
 * - `bridging_log`  : log bridging ke sistem luar (ditangani service terpisah).
 * - `kode_eksternal`: mapping kode eksternal klaim; tidak ada pemakainya.
 * - `diagnosa_prosedur`: relasi diagnosa–prosedur klaim; tidak ada pemakainya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('barang_harga');
        Schema::dropIfExists('bridging_log');
        Schema::dropIfExists('kode_eksternal');
        Schema::dropIfExists('diagnosa_prosedur');
    }

    public function down(): void
    {
        // Tidak dibuat ulang: tabel-tabel ini tidak lagi dipakai aplikasi.
        // Rollback penuh tersedia lewat `migrate:rollback --step` ke migrasi
        // pembuatnya (2026_10_25_000004 & 2026_10_25_000006).
    }
};
