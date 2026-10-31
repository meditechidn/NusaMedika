<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing', function (Blueprint $table) {
            $table->decimal('write_off', 15, 2)->default(0);
            $table->text('write_off_reason')->nullable();
            $table->timestamp('write_off_at')->nullable();
            $table->integer('write_off_by')->nullable();
        });

        Schema::table('billing_pembayaran', function (Blueprint $table) {
            $table->integer('parent_pembayaran_id')->nullable();
            $table->index('parent_pembayaran_id');
        });
    }

    public function down(): void
    {
        Schema::table('billing_pembayaran', function (Blueprint $table) {
            $table->dropIndex(['parent_pembayaran_id']);
            $table->dropColumn('parent_pembayaran_id');
        });

        Schema::table('billing', function (Blueprint $table) {
            $table->dropColumn(['write_off', 'write_off_reason', 'write_off_at', 'write_off_by']);
        });
    }
};
