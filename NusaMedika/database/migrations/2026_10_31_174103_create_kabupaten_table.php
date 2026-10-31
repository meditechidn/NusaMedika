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
        Schema::create('kabupaten', function (Blueprint $table) {
            $table->increments('kabupaten_id');
            $table->integer('provinsi_id')->nullable()->index();
            $table->string('nama_kabupaten')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->integer('kode_wilayah_kabupaten')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kabupaten');
    }
};
