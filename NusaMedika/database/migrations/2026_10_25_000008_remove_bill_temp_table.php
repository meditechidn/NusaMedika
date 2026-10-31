<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('billing', 'status_selesai')) {
            Schema::table('billing', function (Blueprint $table) {
                $table->smallInteger('status_selesai')->default(0)->after('tgl_keluar');
            });
        }

        if (Schema::hasTable('bill_temp') && Schema::hasColumn('bill_temp', 'billing_id')) {
            $billingIds = DB::table('bill_temp')
                ->whereNotNull('billing_id')
                ->where('status_selesai', 1)
                ->pluck('billing_id');

            if ($billingIds->isNotEmpty()) {
                DB::table('billing')->whereIn('billing_id', $billingIds)->update(['status_selesai' => 1]);
            }
        }

        if (Schema::hasColumn('billing_detail', 'bill_temp_id')) {
            Schema::table('billing_detail', function (Blueprint $table) {
                $table->dropColumn('bill_temp_id');
            });
        }

        Schema::dropIfExists('bill_temp');
    }

    public function down(): void
    {
        if (! Schema::hasTable('bill_temp')) {
            Schema::create('bill_temp', function (Blueprint $table) {
                $table->increments('bill_temp_id');
                $table->integer('billing_id')->nullable();
                $table->timestamp('input_time', 6)->nullable();
                $table->integer('input_user_id')->nullable();
                $table->timestamp('mod_time', 6)->nullable();
                $table->integer('mod_user_id')->nullable();
                $table->smallInteger('status_batal')->nullable();
                $table->integer('registrasi_detail_id')->nullable();
                $table->integer('pasien_id')->nullable();
                $table->integer('bagian_id')->nullable();
                $table->integer('nasabah_id')->nullable();
                $table->integer('kelas_ruang_id')->nullable();
                $table->integer('hak_kelas_ruang_id')->nullable();
                $table->timestamp('tgl_bill', 6)->nullable();
                $table->string('sep', 20)->nullable();
                $table->smallInteger('flag_cito')->nullable();
                $table->smallInteger('status_selesai')->nullable();
                $table->smallInteger('bill_temp_jenis')->nullable();
                $table->integer('peresepan_obat_id')->nullable();
                $table->smallInteger('flag_tampil')->nullable();
                $table->integer('status_selisih_kelas')->nullable();

                $table->index('registrasi_detail_id');
            });
        }

        if (! Schema::hasColumn('billing_detail', 'bill_temp_id')) {
            Schema::table('billing_detail', function (Blueprint $table) {
                $table->integer('bill_temp_id')->nullable()->after('registrasi_detail_id');
            });
        }

        if (Schema::hasColumn('billing', 'status_selesai')) {
            Schema::table('billing', function (Blueprint $table) {
                $table->dropColumn('status_selesai');
            });
        }
    }
};
