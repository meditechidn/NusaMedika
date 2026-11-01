<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sub-menu Administrator → Manajemen Master untuk master diet (prasyarat PRE-05).
 *
 * Idempotent: `updateOrInsert` dikunci pada `file_sub_menu`. Menu 6
 * ("Manajemen Master") sudah ada; dua sub-menu baru mengikuti pola
 * `MasterImplementasi` (sub_menu_id 62, `urutan_sub_menu` 23).
 *
 * `file_sub_menu` WAJIB path view lengkap BESERTA nama file blade tanpa
 * ekstensi — dari nilai tersebut `SubMenuRouteServiceProvider::derive()`
 * membentuk nama controller, path view, URI, dan nama route sekaligus.
 *
 * Menu 6 di-_seed_ manual di sini (bukan lewat `ModulMenuSubMenuSeeder` yang
 * digenerate ulang oleh `seeder:sync-master-menu`), konsisten dengan master
 * lain yang ditambahkan setelah generator itu dibuat.
 */
class MasterDietMenuSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Pastikan menu 6 ada (idempotent).
        DB::table('menu')->updateOrInsert(
            ['menu_id' => 6],
            [
                'nama_menu' => 'Manajemen Master',
                'modul_id' => 5,
                'urutan_menu' => 1,
                'input_time' => $now,
                'input_user_id' => 1,
                'status_batal' => 0,
            ]
        );

        $subMenus = [
            [
                'sub_menu_id' => 63,
                'nama_sub_menu' => 'Master Diet',
                'file_sub_menu' => 'Administrator/ManajemenMaster/MasterDiet/master_diet',
                'urutan_sub_menu' => 24,
            ],
            [
                'sub_menu_id' => 64,
                'nama_sub_menu' => 'Master Diagnosa Diet',
                'file_sub_menu' => 'Administrator/ManajemenMaster/MasterDiagnosaDiet/master_diagnosa_diet',
                'urutan_sub_menu' => 25,
            ],
        ];

        foreach ($subMenus as $subMenu) {
            DB::table('sub_menu')->updateOrInsert(
                ['sub_menu_id' => $subMenu['sub_menu_id']],
                array_merge($subMenu, [
                    'menu_id' => 6,
                    'input_time' => $now,
                    'input_user_id' => 1,
                    'status_batal' => 0,
                ])
            );
        }

        // Admin (user 1) diberi akses ke sub-menu baru supaya langsung terlihat
        // di sidebar. `UserSeeder` sendiri juga memberi akses ke seluruh sub_menu
        // aktif, jadi baris ini hanya menutup kasus seeder dijalankan sebagian.
        $admin = DB::table('users')->where('user_id', 1)->first();
        if ($admin) {
            foreach ($subMenus as $subMenu) {
                $ada = DB::table('user_akses')
                    ->where('user_id', $admin->user_id)
                    ->where('sub_menu_id', $subMenu['sub_menu_id'])
                    ->exists();
                if (! $ada) {
                    DB::table('user_akses')->insert([
                        'user_id' => $admin->user_id,
                        'sub_menu_id' => $subMenu['sub_menu_id'],
                        'input_time' => $now,
                        'input_user_id' => 1,
                    ]);
                }
            }
        }
    }
}
