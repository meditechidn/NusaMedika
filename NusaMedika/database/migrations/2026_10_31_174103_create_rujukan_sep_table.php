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
        Schema::create('rujukan_sep', function (Blueprint $table) {
            $table->increments('rujukan_sep_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->integer('registrasi_id')->nullable()->index();
            $table->string('sep', 20)->nullable();
            $table->string('no_rujukan', 20)->nullable();
            $table->string('faskes_rujukan', 1)->nullable();
            $table->string('appr_code_2', 25)->nullable();
            $table->string('appr_code_3', 25)->nullable();
            $table->integer('cetakan_ke')->nullable();
            $table->timestamp('tgl_cetakan')->nullable();
            $table->smallInteger('flag_surat_kontrol')->nullable();
            $table->string('jenis_pelayanan', 20)->nullable();
            $table->string('kelas_rawat', 20)->nullable();
            $table->date('tgl_sep')->nullable();
            $table->date('tgl_rujukan')->nullable();
            $table->string('diagnosa_awal', 10)->nullable();
            $table->string('jenis_peserta', 30)->nullable();
            $table->string('no_kartu', 30)->nullable();
            $table->string('tujuan_kunjungan', 30)->nullable();
            $table->string('no_skdp', 30)->nullable();
            $table->string('status', 20)->nullable();
            $table->smallInteger('flag_prosedur')->nullable();
            $table->integer('dpjp_id')->nullable();
            $table->text('catatan')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rujukan_sep');
    }
};
