<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Selaraskan skema header pemesanan/penerimaan dengan desain saat ini:
     * supplier & distributor disimpan PER ITEM di tabel detail, bukan di header.
     *
     * Kolom `penerimaan.supplier_id` di DB lama masih NOT NULL sehingga setiap
     * penyimpanan penerimaan gagal (controller tidak mengisinya). Kolom
     * `pemesanan.supplier_id`/`distributor_id` juga sisa migration lama yang
     * sudah dihapus dari repo.
     */
    public function up(): void
    {
        $this->hapusKolom('penerimaan', ['supplier_id']);
        $this->hapusKolom('pemesanan', ['supplier_id', 'distributor_id']);
    }

    public function down(): void
    {
        Schema::table('penerimaan', function (Blueprint $table) {
            $table->integer('supplier_id')->nullable();
        });

        Schema::table('pemesanan', function (Blueprint $table) {
            $table->integer('supplier_id')->nullable();
            $table->integer('distributor_id')->nullable();
        });
    }

    private function hapusKolom(string $tabel, array $kolom): void
    {
        $ada = array_values(array_filter($kolom, fn (string $c) => Schema::hasColumn($tabel, $c)));

        if ($ada === []) {
            return;
        }

        Schema::table($tabel, function (Blueprint $table) use ($ada) {
            $table->dropColumn($ada);
        });
    }
};
