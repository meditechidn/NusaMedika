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
        Schema::create('tarif', function (Blueprint $table) {
            $table->increments('tarif_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->integer('tindakan_id')->index();
            $table->integer('kelas_ruang_id')->nullable()->index();
            $table->decimal('tarif', 15)->default(0);
            $table->decimal('jasa_sarana', 15)->nullable()->default(0);
            $table->decimal('jasa_medis', 15)->nullable()->default(0);
            $table->decimal('bhp', 15)->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarif');
    }
};
