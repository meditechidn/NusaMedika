<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MENU_IDS = [21, 22];

    private const SUB_MENU_IDS = [70, 71, 72, 73, 74, 75];

    public function up(): void
    {
        $this->ubahStatus(1);
    }

    public function down(): void
    {
        $this->ubahStatus(0);
    }

    /**
     * Sembunyikan menu Jasa Medis (21) dan Laporan (22) beserta sub-menunya
     * sampai fiturnya siap dipakai.
     */
    private function ubahStatus(int $status): void
    {
        $now = now();

        DB::table('menu')->whereIn('menu_id', self::MENU_IDS)->update([
            'status_batal' => $status,
            'mod_time' => $now,
            'mod_user_id' => 1,
        ]);

        DB::table('sub_menu')->whereIn('sub_menu_id', self::SUB_MENU_IDS)->update([
            'status_batal' => $status,
            'mod_time' => $now,
            'mod_user_id' => 1,
        ]);

        DB::table('user_akses')->whereIn('sub_menu_id', self::SUB_MENU_IDS)->update([
            'status_batal' => $status,
            'mod_time' => $now,
            'mod_user_id' => 1,
        ]);

        foreach (DB::table('users')->pluck('user_id') as $userId) {
            Cache::forget('sidebar_moduls_user_'.$userId);
        }
    }
};
