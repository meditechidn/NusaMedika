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
        Schema::create('mutasi_barang', function (Blueprint $table) {
            $table->increments('mutasi_barang_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->string('no_mutasi', 50)->nullable();
            $table->integer('bagian_asal_id');
            $table->integer('bagian_tujuan_id')->nullable();
            $table->timestamp('tanggal_mutasi', 6)->nullable();
            $table->string('keterangan')->nullable();

            $table->index(['mutasi_barang_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_barang');
    }
};
