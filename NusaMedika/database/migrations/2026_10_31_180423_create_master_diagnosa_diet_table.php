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
        Schema::create('master_diagnosa_diet', function (Blueprint $table) {
            $table->increments('master_diagnosa_diet_id');
            $table->string('kode_diagnosa_diet', 20)->unique();
            $table->string('nama_diagnosa_diet', 150);
            $table->string('keterangan')->nullable();
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
        Schema::dropIfExists('master_diagnosa_diet');
    }
};
