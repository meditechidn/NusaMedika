<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BillingMenuSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('modul')->updateOrInsert(
            ['modul_id' => 11],
            [
                'nama_modul' => 'Billing',
                'icon_modul' => 'fa-solid fa-file-invoice-dollar',
                'urutan_modul' => 11,
                'input_time' => $now,
                'input_user_id' => 1,
                'status_batal' => 0,
            ]
        );

        $menus = [
            ['menu_id' => 20, 'modul_id' => 11, 'nama_menu' => 'Transaksi', 'urutan_menu' => 1],
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
            ['sub_menu_id' => 66, 'menu_id' => 20, 'nama_sub_menu' => 'Daftar Billing', 'file_sub_menu' => 'Billing/Transaksi/DaftarBilling/daftar_billing', 'urutan_sub_menu' => 1],
            ['sub_menu_id' => 67, 'menu_id' => 20, 'nama_sub_menu' => 'Billing Pasien', 'file_sub_menu' => 'Billing/Transaksi/BillingPasien/billing_pasien', 'urutan_sub_menu' => 2],
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

            $exists = DB::table('user_akses')
                ->where('user_id', 1)
                ->where('sub_menu_id', $subMenu['sub_menu_id'])
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->exists();

            if (! $exists) {
                DB::table('user_akses')->insert([
                    'user_id' => 1,
                    'sub_menu_id' => $subMenu['sub_menu_id'],
                    'input_time' => $now,
                    'input_user_id' => 1,
                    'status_batal' => 0,
                ]);
            }
        }

        // Menu yang belum dipakai disembunyikan.
        DB::table('menu')->whereIn('menu_id', [21, 22])->update([
            'status_batal' => 1,
            'mod_time' => $now,
            'mod_user_id' => 1,
        ]);

        DB::table('sub_menu')->whereIn('sub_menu_id', [70, 71, 72, 73, 74, 75, 77, 79])->update([
            'status_batal' => 1,
            'mod_time' => $now,
            'mod_user_id' => 1,
        ]);

        DB::table('user_akses')->whereIn('sub_menu_id', [70, 71, 72, 73, 74, 75, 77, 79])->update([
            'status_batal' => 1,
            'mod_time' => $now,
            'mod_user_id' => 1,
        ]);
    }
}
