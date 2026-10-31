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
        Schema::create('pesan', function (Blueprint $table) {
            $table->increments('pesan_id');
            $table->integer('percakapan_id')->index();
            $table->integer('pengirim_id');
            $table->text('isi');
            $table->timestamp('dibaca_at', 6)->nullable();
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->foreign(['pengirim_id'])->references(['user_id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['percakapan_id'])->references(['percakapan_id'])->on('percakapan')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesan');
    }
};
