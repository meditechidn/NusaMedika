<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sub-menu Inventory → Stock → Stock Opname.
 *
 * Menu 11 ("Stock") sudah ada; sub-menu baru memakai `sub_menu_id` 68 (setelah
 * 67 milik Billing) dengan `urutan_sub_menu` 3. `file_sub_menu` menyimpan path
 * view lengkap tanpa ekstensi agar `SubMenuRouteServiceProvider` menurunkan
 * controller, URI, route, dan URL sidebar secara otomatis.
 *
 * Idempotent: `updateOrInsert` dikunci pada `sub_menu_id`. Hak akses disalin
 * dari pengguna yang sudah punya akses "Stock Barang"/"Kartu Stock" supaya
 * menu langsung terlihat tanpa mengatur ulang Manajemen User.
 */
class StockOpnameMenuSeeder extends Seeder
{
    public const SUB_MENU_ID = 68;

    public function run(): void
    {
        $now = now();

        DB::table('menu')->updateOrInsert(
            ['menu_id' => 11],
            [
                'nama_menu' => 'Stock',
                'modul_id' => 6,
                'urutan_menu' => 3,
                'input_time' => $now,
                'input_user_id' => 1,
                'status_batal' => 0,
            ]
        );

        DB::table('sub_menu')->updateOrInsert(
            ['sub_menu_id' => self::SUB_MENU_ID],
            [
                'menu_id' => 11,
                'nama_sub_menu' => 'Stock Opname',
                'file_sub_menu' => 'Inventory/Stock/StockOpname/stock_opname',
                'urutan_sub_menu' => 3,
                'input_time' => $now,
                'input_user_id' => 1,
                'status_batal' => 0,
            ]
        );

        $userIdPemilikStock = DB::table('user_akses')
            ->whereIn('sub_menu_id', [40, 41])
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->pluck('user_id')
            ->push(1)
            ->unique();

        foreach ($userIdPemilikStock as $userId) {
            $ada = DB::table('user_akses')
                ->where('user_id', $userId)
                ->where('sub_menu_id', self::SUB_MENU_ID)
                ->exists();

            if (! $ada) {
                DB::table('user_akses')->insert([
                    'user_id' => $userId,
                    'sub_menu_id' => self::SUB_MENU_ID,
                    'input_time' => $now,
                    'input_user_id' => 1,
                ]);
            }
        }
    }
}
