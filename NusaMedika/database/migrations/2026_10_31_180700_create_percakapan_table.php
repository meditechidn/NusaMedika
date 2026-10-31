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
        Schema::create('percakapan', function (Blueprint $table) {
            $table->increments('percakapan_id');
            $table->integer('user_a_id');
            $table->integer('user_b_id');
            $table->text('pesan_terakhir')->nullable();
            $table->timestamp('pesan_terakhir_at', 6)->nullable();
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->unique(['user_a_id', 'user_b_id']);
            $table->foreign(['user_a_id'])->references(['user_id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['user_b_id'])->references(['user_id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('percakapan');
    }
};
