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
        Schema::create('pasien', function (Blueprint $table) {
            $table->increments('pasien_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->string('nama_pasien')->nullable();
            $table->string('no_mr', 10)->nullable()->index();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('ktp', 20)->nullable();
            $table->string('sim', 20)->nullable();
            $table->string('paspor', 20)->nullable();
            $table->integer('ayah_pasien_id')->nullable();
            $table->integer('ibu_pasien_id')->nullable();
            $table->bigInteger('kelurahan_id')->nullable()->index();
            $table->string('alamat')->nullable();
            $table->string('no_hp', 15)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('jenis_kelamin', 10)->nullable();
            $table->string('pekerjaan', 100)->nullable();
            $table->string('pendidikan', 100)->nullable();
            $table->smallInteger('flag_general_consent')->nullable();
            $table->string('agama', 100)->nullable();
            $table->string('suku', 100)->nullable();
            $table->string('kebangsaan', 10)->nullable();
            $table->string('no_rfid', 20)->nullable();
            $table->string('gol_darah', 10)->nullable();
            $table->string('no_hp_keluarga', 13)->nullable();
            $table->string('disabilitas', 100)->nullable();
            $table->string('status_perkawinan', 20)->nullable();
            $table->string('pasien_id_old', 20)->nullable();
            $table->string('nama_ibu_kandung', 100)->nullable();
            $table->string('nama_ayah_kandung', 100)->nullable();
            $table->string('id_satu_sehat', 50)->nullable();
            $table->string('domisili')->nullable();
            $table->string('postal_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pasien');
    }
};
