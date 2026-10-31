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
        Schema::create('order_poct_detail', function (Blueprint $table) {
            $table->increments('order_poct_detail_id');
            $table->integer('order_poct_id');
            $table->integer('tindakan_id')->nullable();
            $table->string('nama_tindakan')->nullable();
            $table->decimal('harga', 12)->nullable();
            $table->string('satuan_hasil', 20)->nullable();
            $table->string('nilai_normal', 100)->nullable();
            $table->text('hasil')->nullable();
            $table->smallInteger('flag_abnormal')->nullable();
            $table->smallInteger('status')->default(0);
            $table->integer('petugas_id')->nullable();
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_poct_detail');
    }
};
