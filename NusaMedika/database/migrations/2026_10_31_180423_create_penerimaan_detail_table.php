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
        Schema::create('penerimaan_detail', function (Blueprint $table) {
            $table->increments('penerimaan_detail_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->integer('penerimaan_id');
            $table->integer('barang_id');
            $table->string('no_batch', 50)->nullable();
            $table->decimal('jumlah_terima', 18)->nullable()->default(0);
            $table->decimal('harga_beli', 15)->nullable();
            $table->decimal('harga_jual', 15)->nullable();
            $table->decimal('subtotal', 15)->nullable();
            $table->integer('supplier_id')->nullable();
            $table->integer('distributor_id')->nullable();

            $table->index(['penerimaan_detail_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penerimaan_detail');
    }
};
