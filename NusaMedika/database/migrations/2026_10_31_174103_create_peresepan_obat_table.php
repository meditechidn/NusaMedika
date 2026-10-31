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
        Schema::create('peresepan_obat', function (Blueprint $table) {
            $table->increments('peresepan_obat_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->string('no_resep', 50);
            $table->integer('dokter_id')->nullable()->comment('pegawai_id dokter penulis resep');
            $table->integer('pasien_id')->index();
            $table->integer('registrasi_detail_id')->index();
            $table->smallInteger('status_resep')->nullable()->default(0)->index()->comment('0 = Menunggu, 1 = Selesai, 2 = Batal');
            $table->timestamp('tanggal_resep', 6)->nullable();
            $table->string('keterangan')->nullable();

            $table->index(['peresepan_obat_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peresepan_obat');
    }
};
