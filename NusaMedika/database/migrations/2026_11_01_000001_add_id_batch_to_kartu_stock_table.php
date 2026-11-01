<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kartu_stock', function (Blueprint $table) {
            $table->integer('id_batch')->nullable()->index();
        });

        DB::statement('
            UPDATE kartu_stock AS ks
            SET id_batch = (
                SELECT bb.id_batch
                FROM batch_barang AS bb
                WHERE bb.barang_id = ks.barang_id
                  AND bb.no_batch IS NOT DISTINCT FROM ks.no_batch
                ORDER BY bb.id_batch DESC
                LIMIT 1
            )
            WHERE ks.id_batch IS NULL
        ');

        Schema::table('kartu_stock', function (Blueprint $table) {
            $table->foreign('id_batch')
                ->references('id_batch')
                ->on('batch_barang')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kartu_stock', function (Blueprint $table) {
            $table->dropForeign(['id_batch']);
            $table->dropIndex(['id_batch']);
            $table->dropColumn('id_batch');
        });
    }
};
