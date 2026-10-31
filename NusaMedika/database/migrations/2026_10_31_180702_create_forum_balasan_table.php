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
        Schema::create('forum_balasan', function (Blueprint $table) {
            $table->increments('balasan_id');
            $table->integer('topik_id')->index();
            $table->integer('user_id');
            $table->text('isi');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->foreign(['topik_id'])->references(['topik_id'])->on('forum_topik')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['user_id'])->references(['user_id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_balasan');
    }
};
