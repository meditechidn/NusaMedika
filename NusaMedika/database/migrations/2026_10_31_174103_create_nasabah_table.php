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
        Schema::create('nasabah', function (Blueprint $table) {
            $table->increments('nasabah_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->string('nama_nasabah')->nullable();
            $table->string('jenis_nasabah', 20)->nullable();
            $table->string('email_nasabah', 100)->nullable();
            $table->string('alamat_nasabah', 250)->nullable();
            $table->string('kode_penjamin', 30)->nullable();
            $table->string('jenis_penjamin', 20)->nullable()->default('UMUM');
            $table->smallInteger('is_aktif')->nullable()->default(1);
            $table->string('logo_url')->nullable();
            $table->text('catatan')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nasabah');
    }
};
