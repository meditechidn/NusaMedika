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
        Schema::create('registrasi_detail', function (Blueprint $table) {
            $table->increments('registrasi_detail_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->integer('registrasi_id')->nullable()->index();
            $table->timestamp('tgl_daftar', 6)->nullable();
            $table->integer('bagian_id')->nullable()->index();
            $table->integer('kelas_id')->nullable();
            $table->integer('hak_kelas_id')->nullable();
            $table->integer('bagian_asal_id')->nullable();
            $table->string('ket_catatan', 1000)->nullable();
            $table->string('terima_dari', 100)->nullable();
            $table->integer('flag_covid')->nullable();
            $table->timestamp('check_in')->nullable();
            $table->timestamp('check_out')->nullable();
            $table->integer('kriteria_covid')->nullable();
            $table->integer('asal_daftar')->nullable();
            $table->string('masa_berlaku')->nullable();
            $table->string('limit_berobat')->nullable();
            $table->string('catatan_khusus')->nullable();
            $table->integer('verifikator_user_id')->nullable();
            $table->timestamp('verifikator_input_time', 6)->nullable();
            $table->json('handover')->nullable();
            $table->integer('lokasi_daftar')->nullable();
            $table->string('lokasi_rawat', 100)->nullable();
            $table->string('service_req_id')->nullable();
            $table->string('specimen_id')->nullable();
            $table->decimal('harga_suket', 12)->nullable();
            $table->integer('suket_mcu_id')->nullable();
            $table->string('triase', 50)->nullable();
            $table->string('cara_masuk', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrasi_detail');
    }
};
