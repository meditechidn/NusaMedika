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
        Schema::create('stock', function (Blueprint $table) {
            $table->increments('stock_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->integer('barang_id');
            $table->string('no_batch', 50)->nullable();
            $table->integer('bagian_id');
            $table->decimal('jumlah', 18)->nullable()->default(0);
            $table->decimal('harga_beli', 15)->nullable();
            $table->decimal('harga_jual', 15)->nullable();
            $table->date('tgl_expired')->nullable();
            $table->integer('id_batch')->nullable();
        });
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS stock_barang_id_bagian_id_no_batch_harga_jual_unique ON stock (barang_id, bagian_id, no_batch, harga_jual) WHERE (status_batal IS NULL) OR (status_batal = 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS stock_barang_id_bagian_id_no_batch_harga_jual_unique');
        Schema::dropIfExists('stock');
    }
};
