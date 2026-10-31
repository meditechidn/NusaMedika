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
        Schema::create('form_blanko', function (Blueprint $table) {
            $table->increments('form_blanko_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->string('kode_blanko', 50)->unique();
            $table->string('nama_blanko', 150);
            $table->string('slug', 150)->unique();
            $table->text('deskripsi')->nullable();
            $table->integer('menu_induk')->nullable();
            $table->integer('sub_induk')->nullable();
            $table->integer('extra_induk')->nullable();
            $table->string('kategori', 100)->nullable();
            $table->string('departemen', 100)->nullable();
            $table->smallInteger('is_aktif')->default(1);
            $table->string('ukuran_halaman', 20)->default('A4');
            $table->string('orientasi_halaman', 10)->default('portrait');
            $table->boolean('tampilkan_identitas_pasien')->default(true);
            $table->boolean('tampilkan_logo')->default(true);
            $table->text('kop_surat')->nullable();
            $table->json('filter_adaptif')->nullable();
            $table->smallInteger('versi')->default(1);
            $table->timestamp('publish_time', 6)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_blanko');
    }
};
