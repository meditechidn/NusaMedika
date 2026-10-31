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
        Schema::create('forum_topik', function (Blueprint $table) {
            $table->increments('topik_id');
            $table->string('judul', 200);
            $table->text('isi');
            $table->string('tipe', 20)->default('PUBLIK');
            $table->integer('target_id')->nullable();
            $table->integer('user_id');
            $table->timestamp('terakhir_aktif_at', 6)->nullable();
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->index(['tipe', 'target_id']);
            $table->foreign(['user_id'])->references(['user_id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_topik');
    }
};
