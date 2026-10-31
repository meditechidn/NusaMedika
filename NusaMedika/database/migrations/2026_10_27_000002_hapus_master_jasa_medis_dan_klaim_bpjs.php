<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SUB_MENU_IDS = [77, 79];

    public function up(): void
    {
        $now = now();

        DB::table('sub_menu')->whereIn('sub_menu_id', self::SUB_MENU_IDS)->update([
            'status_batal' => 1,
            'mod_time' => $now,
            'mod_user_id' => 1,
        ]);

        DB::table('user_akses')->whereIn('sub_menu_id', self::SUB_MENU_IDS)->update([
            'status_batal' => 1,
            'mod_time' => $now,
            'mod_user_id' => 1,
        ]);

        DB::table('billing_pengaturan')->where('kunci', 'jasa_medis_wajib_tutup')->delete();

        Schema::dropIfExists('ina_cbg_result');
        Schema::dropIfExists('klaim_log');
        Schema::dropIfExists('klaim_verifikasi');
        Schema::dropIfExists('klaim_berkas');
        Schema::dropIfExists('klaim_detail');
        Schema::dropIfExists('klaim');

        foreach (DB::table('users')->pluck('user_id') as $userId) {
            Cache::forget('sidebar_moduls_user_'.$userId);
        }
    }

    public function down(): void
    {
        $now = now();

        DB::table('sub_menu')->whereIn('sub_menu_id', self::SUB_MENU_IDS)->update([
            'status_batal' => 0,
            'mod_time' => $now,
            'mod_user_id' => 1,
        ]);

        DB::table('user_akses')->whereIn('sub_menu_id', self::SUB_MENU_IDS)->update([
            'status_batal' => 0,
            'mod_time' => $now,
            'mod_user_id' => 1,
        ]);

        DB::table('billing_pengaturan')->updateOrInsert(
            ['kunci' => 'jasa_medis_wajib_tutup'],
            [
                'nilai' => '1',
                'tipe' => 'boolean',
                'grup' => 'jasa_medis',
                'keterangan' => 'Hitung jasa medis saat tutup billing',
                'input_time' => $now,
                'input_user_id' => 1,
                'status_batal' => 0,
            ]
        );

        if (! Schema::hasTable('klaim')) {
            Schema::create('klaim', function (Blueprint $table) {
                $table->increments('klaim_id');
                $table->timestamp('input_time', 6)->nullable();
                $table->integer('input_user_id')->nullable();
                $table->timestamp('mod_time', 6)->nullable();
                $table->integer('mod_user_id')->nullable();
                $table->smallInteger('status_batal')->nullable();

                $table->integer('billing_id');
                $table->integer('registrasi_id');
                $table->integer('nasabah_id')->nullable();
                $table->string('no_sep', 30)->nullable();
                $table->string('no_klaim', 50)->nullable()->unique();
                $table->string('jenis_klaim', 30)->default('BPJS');
                $table->date('tgl_klaim')->nullable();
                $table->smallInteger('status')->default(0);
                $table->decimal('total_diajukan', 15, 2)->default(0);
                $table->decimal('total_disetujui', 15, 2)->default(0);
                $table->decimal('total_dibayar', 15, 2)->default(0);
                $table->text('catatan')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('paid_at')->nullable();

                $table->index('billing_id');
                $table->index('registrasi_id');
                $table->index('status');
                $table->index('no_sep');
            });
        }

        if (! Schema::hasTable('klaim_detail')) {
            Schema::create('klaim_detail', function (Blueprint $table) {
                $table->increments('klaim_detail_id');
                $table->timestamp('input_time', 6)->nullable();
                $table->integer('input_user_id')->nullable();
                $table->timestamp('mod_time', 6)->nullable();
                $table->integer('mod_user_id')->nullable();
                $table->smallInteger('status_batal')->nullable();

                $table->integer('klaim_id');
                $table->integer('billing_detail_id')->nullable();
                $table->string('kode', 30)->nullable();
                $table->string('nama', 255);
                $table->decimal('qty', 15, 2)->default(1);
                $table->decimal('tarif', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->string('kode_ina_cbg', 30)->nullable();
                $table->text('keterangan')->nullable();

                $table->index('klaim_id');
                $table->index('billing_detail_id');
            });
        }

        if (! Schema::hasTable('klaim_berkas')) {
            Schema::create('klaim_berkas', function (Blueprint $table) {
                $table->increments('klaim_berkas_id');
                $table->timestamp('input_time', 6)->nullable();
                $table->integer('input_user_id')->nullable();
                $table->timestamp('mod_time', 6)->nullable();
                $table->integer('mod_user_id')->nullable();
                $table->smallInteger('status_batal')->nullable();

                $table->integer('klaim_id');
                $table->string('jenis_berkas', 50);
                $table->string('nama_file', 255)->nullable();
                $table->string('path', 255)->nullable();
                $table->string('status', 20)->default('BELUM_ADA');
                $table->text('catatan')->nullable();
                $table->timestamp('uploaded_at')->nullable();
                $table->integer('uploaded_by')->nullable();

                $table->index('klaim_id');
            });
        }

        if (! Schema::hasTable('klaim_verifikasi')) {
            Schema::create('klaim_verifikasi', function (Blueprint $table) {
                $table->increments('klaim_verifikasi_id');
                $table->timestamp('input_time', 6)->nullable();
                $table->integer('input_user_id')->nullable();
                $table->timestamp('mod_time', 6)->nullable();
                $table->integer('mod_user_id')->nullable();
                $table->smallInteger('status_batal')->nullable();

                $table->integer('klaim_id');
                $table->smallInteger('status')->default(0);
                $table->text('catatan')->nullable();
                $table->string('verifikator', 150)->nullable();
                $table->timestamp('tanggal')->nullable();

                $table->index('klaim_id');
            });
        }

        if (! Schema::hasTable('klaim_log')) {
            Schema::create('klaim_log', function (Blueprint $table) {
                $table->increments('klaim_log_id');
                $table->integer('klaim_id');
                $table->string('aksi', 50);
                $table->json('data_lama')->nullable();
                $table->json('data_baru')->nullable();
                $table->integer('user_id')->nullable();
                $table->string('ip', 45)->nullable();
                $table->timestamp('waktu')->nullable();

                $table->index('klaim_id');
            });
        }

        if (! Schema::hasTable('ina_cbg_result')) {
            Schema::create('ina_cbg_result', function (Blueprint $table) {
                $table->increments('ina_cbg_result_id');
                $table->timestamp('input_time', 6)->nullable();
                $table->integer('input_user_id')->nullable();
                $table->timestamp('mod_time', 6)->nullable();
                $table->integer('mod_user_id')->nullable();
                $table->smallInteger('status_batal')->nullable();

                $table->integer('klaim_id');
                $table->string('kode_ina_cbg', 30)->nullable();
                $table->string('deskripsi', 255)->nullable();
                $table->decimal('tarif', 15, 2)->default(0);
                $table->string('severity', 20)->nullable();
                $table->string('kelas', 20)->nullable();
                $table->string('status', 20)->default('DRAFT');
                $table->json('payload')->nullable();

                $table->index('klaim_id');
            });
        }
    }
};
