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
        Schema::create('kecamatan', function (Blueprint $table) {
            $table->increments('kecamatan_id');
            $table->integer('kabupaten_id')->nullable()->index();
            $table->string('nama_kecamatan')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->integer('kode_wilayah_kecamatan')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kecamatan');
    }
};
