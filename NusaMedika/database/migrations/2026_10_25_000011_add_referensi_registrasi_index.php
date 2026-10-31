<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrasi', function (Blueprint $table) {
            $table->index('referensi_registrasi_id', 'registrasi_referensi_index');
        });
    }

    public function down(): void
    {
        Schema::table('registrasi', function (Blueprint $table) {
            $table->dropIndex('registrasi_referensi_index');
        });
    }
};
