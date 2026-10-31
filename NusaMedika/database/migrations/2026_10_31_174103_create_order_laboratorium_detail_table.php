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
        Schema::create('order_laboratorium_detail', function (Blueprint $table) {
            $table->increments('order_laboratorium_detail_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->integer('order_laboratorium_id')->index();
            $table->integer('tindakan_id')->nullable()->index();
            $table->string('nama_tindakan');
            $table->decimal('harga', 12)->default(0);
            $table->string('satuan_hasil', 20)->nullable();
            $table->string('nilai_normal', 100)->nullable();
            $table->text('hasil')->nullable();
            $table->smallInteger('flag_abnormal')->nullable();
            $table->smallInteger('status')->default(0);
            $table->integer('petugas_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_laboratorium_detail');
    }
};
