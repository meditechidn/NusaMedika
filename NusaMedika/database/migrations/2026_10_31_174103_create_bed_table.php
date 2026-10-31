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
        Schema::create('bed', function (Blueprint $table) {
            $table->increments('bed_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->integer('bagian_id')->nullable();
            $table->string('no_kamar', 50)->nullable();
            $table->string('nama_bed', 30)->nullable();
            $table->integer('status_bed')->nullable();
            $table->integer('kelas_id')->nullable();
            $table->timestamp('tgl_masuk', 6)->nullable();
            $table->integer('pasien_id_1')->nullable();
            $table->integer('pasien_id_2')->nullable();
            $table->integer('flag_isolasi')->nullable()->comment('1 = Isolasi
2 = Normal
3 = Covid 19');
            $table->string('keterangan', 250)->nullable();
            $table->integer('flag_isolasi_pressure')->nullable()->comment('1 = Negative Pressure
2 = Non Negative Pressure');
            $table->integer('flag_ventilator')->nullable()->comment('1 = Dengan Ventilator
2 = Tanpa Ventilator');
            $table->integer('lokasi')->nullable();
            $table->integer('flag_neonatus')->nullable()->comment('0 = Tidak Neonatus
1 = Neonatus');
            $table->smallInteger('siap_kirim')->nullable();
            $table->string('kodekelas', 10)->nullable();
            $table->string('namakelas', 50)->nullable();
            $table->integer('flag_extra')->nullable();
            $table->integer('bor')->nullable();
            $table->smallInteger('flag_persiapan_pulang')->nullable()->comment('1 = Persiapan Pulang diaktifkan
2 = Tidak');
            $table->timestamp('tgl_pulang', 6)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bed');
    }
};
