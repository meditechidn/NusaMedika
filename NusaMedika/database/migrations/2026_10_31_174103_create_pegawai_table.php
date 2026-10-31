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
        Schema::create('pegawai', function (Blueprint $table) {
            $table->increments('pegawai_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->string('nama_pegawai')->nullable();
            $table->string('nip', 20)->nullable();
            $table->integer('bagian_id')->nullable()->index();
            $table->integer('profesi_id')->nullable()->index();
            $table->timestamp('tgl_awal_sip')->nullable();
            $table->timestamp('tgl_akhir_sip')->nullable();
            $table->string('sip', 100)->nullable();
            $table->timestamp('tgl_awal_str')->nullable();
            $table->timestamp('tgl_akhir_str')->nullable();
            $table->string('str', 100)->nullable();
            $table->string('ttd', 100)->nullable();
            $table->string('inacbg_id', 100)->nullable();
            $table->integer('jabatan_id')->nullable()->index();
            $table->integer('status_kepegawaian_id')->nullable()->index();
            $table->string('foto', 100)->nullable();
            $table->string('nik', 20)->nullable();
            $table->string('id_satu_sehat', 20)->nullable();
            $table->bigInteger('no_rfid')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai');
    }
};
