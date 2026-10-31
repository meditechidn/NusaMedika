<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kartu_stock', function (Blueprint $table) {
            $table->increments('kartu_stock_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->integer('barang_id');
            $table->string('no_batch', 50)->nullable();
            $table->integer('bagian_id');
            $table->timestamp('tanggal', 6)->nullable();
            $table->smallInteger('jenis_mutasi')->nullable();
            $table->integer('ref_penerimaan_detail_id')->nullable();
            $table->integer('ref_mutasi_barang_detail_id')->nullable();
            $table->decimal('qty_masuk', 18)->nullable()->default(0);
            $table->decimal('qty_keluar', 18)->nullable()->default(0);
            $table->decimal('saldo_sebelum', 18)->nullable()->default(0);
            $table->decimal('saldo_sesudah', 18)->nullable()->default(0);
            $table->string('keterangan')->nullable();
            $table->decimal('harga_beli', 15)->nullable();
            $table->decimal('harga_jual', 15)->nullable();
            $table->date('tgl_expired')->nullable();
            $table->integer('ref_peresepan_obat_detail_id')->nullable();
            $table->integer('ref_emr_id')->nullable()->index();

            $table->index(['kartu_stock_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kartu_stock');
    }
};
