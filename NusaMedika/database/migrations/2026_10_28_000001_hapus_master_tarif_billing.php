<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MENU_ID = 23;

    public function up(): void
    {
        $subMenuIds = DB::table('sub_menu')->where('menu_id', self::MENU_ID)->pluck('sub_menu_id');

        if ($subMenuIds->isNotEmpty()) {
            DB::table('user_akses')->whereIn('sub_menu_id', $subMenuIds)->delete();
        }

        DB::table('sub_menu')->where('menu_id', self::MENU_ID)->delete();
        DB::table('menu')->where('menu_id', self::MENU_ID)->delete();

        foreach (DB::table('users')->pluck('user_id') as $userId) {
            Cache::forget('sidebar_moduls_user_'.$userId);
        }
    }

    public function down(): void
    {
        $now = now();

        DB::table('menu')->updateOrInsert(
            ['menu_id' => self::MENU_ID],
            [
                'modul_id' => 5,
                'nama_menu' => 'Manajemen Billing',
                'urutan_menu' => 5,
                'input_time' => $now,
                'input_user_id' => 1,
                'status_batal' => 0,
            ]
        );

        DB::table('sub_menu')->updateOrInsert(
            ['sub_menu_id' => 76],
            [
                'menu_id' => self::MENU_ID,
                'nama_sub_menu' => 'Master Tarif Billing',
                'file_sub_menu' => 'Administrator/ManajemenBilling/MasterTarifBilling/master_tarif_billing',
                'urutan_sub_menu' => 1,
                'input_time' => $now,
                'input_user_id' => 1,
                'status_batal' => 0,
            ]
        );

        DB::table('user_akses')->updateOrInsert(
            ['user_id' => 1, 'sub_menu_id' => 76],
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
