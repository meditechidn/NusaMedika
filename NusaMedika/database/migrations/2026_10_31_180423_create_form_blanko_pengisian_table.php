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
        Schema::create('form_blanko_pengisian', function (Blueprint $table) {
            $table->increments('form_blanko_pengisian_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->integer('form_blanko_id');
            $table->integer('registrasi_detail_id')->index();
            $table->json('data');
            $table->json('ringkasan')->nullable();
            $table->smallInteger('jumlah_terisi')->default(0);

            $table->index(['form_blanko_id', 'registrasi_detail_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_blanko_pengisian');
    }
};
