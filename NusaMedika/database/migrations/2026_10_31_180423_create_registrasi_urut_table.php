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
        Schema::create('registrasi_urut', function (Blueprint $table) {
            $table->increments('registrasi_urut_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->integer('registrasi_detail_id')->nullable()->index();
            $table->integer('pegawai_id')->nullable()->index();
            $table->integer('bagian_id')->nullable()->index();
            $table->integer('urutan')->nullable();
            $table->timestamp('tgl_urut', 6)->nullable();
            $table->smallInteger('status_check_in')->nullable();
            $table->smallInteger('status_panggil')->nullable();
            $table->timestamp('tgl_panggil', 6)->nullable();
            $table->integer('urutan_ttv')->nullable();
            $table->integer('urutan_check_in')->nullable();
            $table->timestamp('tgl_check_in', 6)->nullable();
            $table->smallInteger('status_check_in_rs')->nullable();
            $table->timestamp('tgl_check_in_rs', 6)->nullable();
            $table->timestamp('tgl_jam_wa_konfirmasi', 6)->nullable();
            $table->string('flag_konfirmasi', 1)->nullable();
            $table->timestamp('tgl_jam_konfirmasi', 6)->nullable();
            $table->timestamp('tgl_jam_checkin', 6)->nullable();
            $table->string('status_antrian')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrasi_urut');
    }
};
