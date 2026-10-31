<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('barang_jenis', function (Blueprint $table) {
            $table->increments('barang_jenis_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->string('nama_jenis_barang');

            $table->index(['barang_jenis_id']);
        });

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS barang_jenis_nama_unik ON barang_jenis (lower(btrim((nama_jenis_barang)::text))) WHERE (status_batal IS NULL) OR (status_batal = 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS barang_jenis_nama_unik');
        DB::statement('DROP INDEX IF EXISTS barang_jenis_nama_unik');
        Schema::dropIfExists('barang_jenis');
    }
};
