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
        Schema::create('pindah_ruangan', function (Blueprint $table) {
            $table->increments('pindah_ruangan_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->integer('pasien_id')->nullable()->index();
            $table->integer('registrasi_detail_id')->nullable();
            $table->integer('bed_asal_id')->nullable();
            $table->integer('bagian_asal_id')->nullable();
            $table->integer('bed_tujuan_id')->nullable();
            $table->integer('bagian_tujuan_id')->nullable()->index();
            $table->smallInteger('status')->default(0);
            $table->text('keterangan')->nullable();
            $table->integer('disetujui_user_id')->nullable();
            $table->timestamp('disetujui_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pindah_ruangan');
    }
};
