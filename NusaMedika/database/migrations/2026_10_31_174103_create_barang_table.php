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
        Schema::create('barang', function (Blueprint $table) {
            $table->increments('barang_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->string('nama_barang');
            $table->integer('jenis_barang_id');
            $table->string('kode_barang', 50)->nullable();
            $table->integer('satuan_id')->nullable();
            $table->smallInteger('is_racikan')->nullable()->default(0);
            $table->smallInteger('is_fornas')->nullable()->default(0);
            $table->smallInteger('kategori_barang')->nullable()->default(0);
            $table->boolean('flag_alert')->default(false);

            $table->index(['barang_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};
