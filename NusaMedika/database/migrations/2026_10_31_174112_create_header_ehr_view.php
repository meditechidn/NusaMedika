<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("CREATE VIEW \"header_ehr\" AS SELECT concat_ws('.'::text, dashboard_menu.dashboard_menu_id, dashboard_menu_sub.dashboard_menu_sub_id, dashboard_menu_sub_extra.dashboard_menu_sub_extra_id) AS id_dash_menu,
    dashboard_menu.nama_menu,
    dashboard_menu_sub.nama_sub_menu,
    dashboard_menu_sub_extra.nama_sub_menu_extra,
    dashboard_menu.dashboard_menu_id,
    dashboard_menu_sub.dashboard_menu_sub_id,
    dashboard_menu_sub_extra.dashboard_menu_sub_extra_id
   FROM ((dashboard_menu
     LEFT JOIN dashboard_menu_sub ON ((dashboard_menu_sub.dashboard_menu_id = dashboard_menu.dashboard_menu_id)))
     LEFT JOIN dashboard_menu_sub_extra ON ((dashboard_menu_sub_extra.dashboard_menu_sub_id = dashboard_menu_sub.dashboard_menu_sub_id)))
  ORDER BY dashboard_menu.dashboard_menu_id, dashboard_menu_sub.dashboard_menu_sub_id, dashboard_menu_sub_extra.dashboard_menu_sub_extra_id;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS "header_ehr"');
    }
};
