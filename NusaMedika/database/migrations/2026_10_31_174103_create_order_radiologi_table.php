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
        Schema::create('order_radiologi', function (Blueprint $table) {
            $table->increments('order_radiologi_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->string('no_order', 30);
            $table->integer('pasien_id');
            $table->integer('registrasi_id')->index();
            $table->integer('registrasi_detail_id')->index();
            $table->integer('registrasi_detail_tujuan_id')->index();
            $table->integer('bagian_asal_id');
            $table->integer('bagian_tujuan_id')->index();
            $table->integer('dokter_id')->nullable();
            $table->string('prioritas', 15)->nullable();
            $table->smallInteger('status_order')->default(0)->index();
            $table->timestamp('tanggal_order')->nullable();
            $table->timestamp('tanggal_terima')->nullable();
            $table->timestamp('tanggal_hasil')->nullable();
            $table->integer('petugas_pelaksana_id')->nullable();
            $table->smallInteger('kelas_id')->nullable();
            $table->integer('hak_kelas_id')->nullable();
            $table->text('keterangan')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_radiologi');
    }
};
