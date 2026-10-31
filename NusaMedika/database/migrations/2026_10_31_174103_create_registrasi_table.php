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
        Schema::create('registrasi', function (Blueprint $table) {
            $table->increments('registrasi_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->integer('pasien_id')->nullable()->index();
            $table->integer('pasien_nasabah_id')->nullable();
            $table->integer('referensi_registrasi_id')->nullable()->index();
            $table->timestamp('tgl_masuk', 6)->nullable();
            $table->timestamp('tgl_keluar', 6)->nullable();
            $table->string('jenis_rawat', 3)->nullable();
            $table->string('prioritas', 20)->nullable();
            $table->text('memo')->nullable();
            $table->integer('pasien_nasabah_id_2')->nullable();
            $table->integer('pasien_nasabah_id_3')->nullable();
            $table->smallInteger('flag_online')->nullable();
            $table->text('memo_adru')->nullable();
            $table->timestamp('awal_rawat_pelni')->nullable();
            $table->timestamp('akhir_rawat_pelni')->nullable();
            $table->smallInteger('status_pulang_vclaim')->nullable();
            $table->timestamp('tgl_keluar_old', 6)->nullable();
            $table->string('id_satu_sehat', 50)->nullable();
            $table->integer('peresepan_obat_id')->nullable();
            $table->integer('jumlah_iter')->nullable();
            $table->integer('flag_sirs')->nullable();
            $table->integer('id_perujuk')->nullable();
            $table->text('alasan_pulang')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrasi');
    }
};
