<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MENU_IDS = [21, 22];

    private const SUB_MENU_IDS = [70, 71, 72, 73, 74, 75, 77, 79];

    public function up(): void
    {
        DB::table('user_akses')->whereIn('sub_menu_id', self::SUB_MENU_IDS)->delete();
        DB::table('sub_menu')->whereIn('sub_menu_id', self::SUB_MENU_IDS)->delete();
        DB::table('menu')->whereIn('menu_id', self::MENU_IDS)->delete();

        foreach (DB::table('users')->pluck('user_id') as $userId) {
            Cache::forget('sidebar_moduls_user_'.$userId);
        }
    }

    public function down(): void
    {
        $now = now();

        $menus = [
            ['menu_id' => 21, 'modul_id' => 11, 'nama_menu' => 'Jasa Medis', 'urutan_menu' => 2],
            ['menu_id' => 22, 'modul_id' => 11, 'nama_menu' => 'Laporan', 'urutan_menu' => 3],
        ];

        foreach ($menus as $menu) {
            DB::table('menu')->updateOrInsert(
                ['menu_id' => $menu['menu_id']],
                array_merge($menu, [
                    'input_time' => $now,
                    'input_user_id' => 1,
                    'status_batal' => 0,
                ])
            );
        }

        $subMenus = [
            ['sub_menu_id' => 70, 'menu_id' => 21, 'nama_sub_menu' => 'Jasa Medis Rawat Jalan', 'file_sub_menu' => 'Billing/JasaMedis/JasaMedisRawatJalan/jasa_medis_rawat_jalan', 'urutan_sub_menu' => 1],
            ['sub_menu_id' => 71, 'menu_id' => 21, 'nama_sub_menu' => 'Jasa Medis Rawat Inap', 'file_sub_menu' => 'Billing/JasaMedis/JasaMedisRawatInap/jasa_medis_rawat_inap', 'urutan_sub_menu' => 2],
            ['sub_menu_id' => 72, 'menu_id' => 22, 'nama_sub_menu' => 'Laporan Pendapatan', 'file_sub_menu' => 'Billing/Laporan/LaporanPendapatan/laporan_pendapatan', 'urutan_sub_menu' => 1],
            ['sub_menu_id' => 73, 'menu_id' => 22, 'nama_sub_menu' => 'Laporan Kasir', 'file_sub_menu' => 'Billing/Laporan/LaporanKasir/laporan_kasir', 'urutan_sub_menu' => 2],
            ['sub_menu_id' => 74, 'menu_id' => 22, 'nama_sub_menu' => 'Laporan Piutang', 'file_sub_menu' => 'Billing/Laporan/LaporanPiutang/laporan_piutang', 'urutan_sub_menu' => 3],
            ['sub_menu_id' => 75, 'menu_id' => 22, 'nama_sub_menu' => 'Laporan Obat & BMHP', 'file_sub_menu' => 'Billing/Laporan/LaporanObatBmhp/laporan_obat_bmhp', 'urutan_sub_menu' => 4],
            ['sub_menu_id' => 77, 'menu_id' => 23, 'nama_sub_menu' => 'Master Jasa Medis', 'file_sub_menu' => 'Administrator/ManajemenBilling/JasaMedisRule/jasa_medis_rule', 'urutan_sub_menu' => 2],
            ['sub_menu_id' => 79, 'menu_id' => 23, 'nama_sub_menu' => 'Klaim BPJS', 'file_sub_menu' => 'Administrator/ManajemenBilling/Klaim/klaim', 'urutan_sub_menu' => 4],
        ];

        foreach ($subMenus as $subMenu) {
            DB::table('sub_menu')->updateOrInsert(
                ['sub_menu_id' => $subMenu['sub_menu_id']],
                array_merge($subMenu, [
                    'input_time' => $now,
                    'input_user_id' => 1,
                    'status_batal' => 0,
                ])
            );

            DB::table('user_akses')->updateOrInsert(
                ['user_id' => 1, 'sub_menu_id' => $subMenu['sub_menu_id']],
                [
                    'input_time' => $now,
                    'input_user_id' => 1,
                    'status_batal' => 0,
                ]
            );
        }

        foreach (DB::table('users')->pluck('user_id') as $userId) {
            Cache::forget('sidebar_moduls_user_'.$userId);
        }
    }
};
