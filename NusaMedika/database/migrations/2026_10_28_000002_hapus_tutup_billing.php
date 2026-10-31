<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SUB_MENU_ID = 69;

    public function up(): void
    {
        DB::table('user_akses')->where('sub_menu_id', self::SUB_MENU_ID)->delete();
        DB::table('sub_menu')->where('sub_menu_id', self::SUB_MENU_ID)->delete();

        foreach (DB::table('users')->pluck('user_id') as $userId) {
            Cache::forget('sidebar_moduls_user_'.$userId);
        }
    }

    public function down(): void
    {
        $now = now();

        DB::table('sub_menu')->updateOrInsert(
            ['sub_menu_id' => self::SUB_MENU_ID],
            [
                'menu_id' => 20,
                'nama_sub_menu' => 'Tutup Billing',
                'file_sub_menu' => 'Billing/Transaksi/TutupBilling/tutup_billing',
                'urutan_sub_menu' => 4,
                'input_time' => $now,
                'input_user_id' => 1,
                'status_batal' => 0,
            ]
        );

        DB::table('user_akses')->updateOrInsert(
            ['user_id' => 1, 'sub_menu_id' => self::SUB_MENU_ID],
            [
                'input_time' => $now,
                'input_user_id' => 1,
                'status_batal' => 0,
            ]
        );

        foreach (DB::table('users')->pluck('user_id') as $userId) {
            Cache::forget('sidebar_moduls_user_'.$userId);
        }
    }
};
