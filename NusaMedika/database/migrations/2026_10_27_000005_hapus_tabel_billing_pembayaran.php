<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Billing hanya memakai billing + billing_detail. Semua alur pembayaran,
     * deposit, piutang, penjamin, log, counter, pengaturan, tenaga, kasir
     * shift, dan paket tindakan dihapus. Penghapusan bersifat permanen;
     * rollback sengaja tidak mengembalikan tabel.
     */
    public function up(): void
    {
        DB::table('user_akses')->whereIn('sub_menu_id', [68, 78])->delete();
        DB::table('sub_menu')->whereIn('sub_menu_id', [68, 78])->delete();

        $tables = [
            'billing_pembayaran_detail',
            'billing_pembayaran',
            'billing_deposit_mutasi',
            'billing_deposit',
            'billing_piutang_pembayaran',
            'billing_piutang',
            'billing_penjamin',
            'billing_log',
            'billing_counter',
            'billing_pengaturan',
            'billing_tenaga',
            'kasir_shift_denominasi',
            'kasir_shift',
            'paket_tindakan_detail',
            'paket_tindakan',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Penghapusan tabel bersifat permanen; tidak ada rollback.
    }
};
