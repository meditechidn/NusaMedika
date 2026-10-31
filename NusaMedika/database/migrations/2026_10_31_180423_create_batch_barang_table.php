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
        Schema::create('batch_barang', function (Blueprint $table) {
            $table->increments('id_batch');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->integer('barang_id')->index();
            $table->string('no_batch')->index();
            $table->decimal('harga_beli', 15)->default(0);
            $table->decimal('harga_jual', 15)->default(0);
            $table->date('tgl_expired')->nullable();

            $table->index(['id_batch']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_barang');
    }
};
