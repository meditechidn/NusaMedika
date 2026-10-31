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
        Schema::create('notifikasi_penerima', function (Blueprint $table) {
            $table->increments('notifikasi_penerima_id');
            $table->integer('notifikasi_id');
            $table->integer('user_id');
            $table->smallInteger('is_read')->default(0);
            $table->timestamp('dibaca_time', 6)->nullable();
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->index(['user_id', 'is_read']);

            $table->foreign('notifikasi_id')->references('notifikasi_id')->on('notifikasi')->cascadeOnDelete();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasi_penerima');
    }
};
