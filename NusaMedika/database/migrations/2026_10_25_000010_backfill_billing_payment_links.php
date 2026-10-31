<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('billing_pembayaran')
            ->whereNull('registrasi_id')
            ->update([
                'registrasi_id' => DB::raw('(select b.registrasi_id from billing b where b.billing_id = billing_pembayaran.billing_id)'),
            ]);

        DB::table('billing_pembayaran_detail')
            ->whereNull('billing_id')
            ->update([
                'billing_id' => DB::raw('(select bp.billing_id from billing_pembayaran bp where bp.billing_pembayaran_id = billing_pembayaran_detail.billing_pembayaran_id)'),
            ]);
    }

    public function down(): void
    {
        //
    }
};
