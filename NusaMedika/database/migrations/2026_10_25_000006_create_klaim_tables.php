<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kode_eksternal', function (Blueprint $table) {
            $table->increments('kode_eksternal_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->string('jenis', 30);
            $table->integer('internal_id')->nullable();
            $table->string('kode', 30);
            $table->string('nama', 255)->nullable();
            $table->string('sumber', 30)->nullable();
            $table->smallInteger('is_aktif')->default(1);
            $table->text('keterangan')->nullable();

            $table->index(['jenis', 'internal_id']);
            $table->index('kode');
        });

        Schema::create('diagnosa_prosedur', function (Blueprint $table) {
            $table->increments('diagnosa_prosedur_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->integer('registrasi_id');
            $table->string('jenis', 20);
            $table->string('kode', 20);
            $table->string('nama', 255)->nullable();
            $table->string('tipe', 20)->default('PRIMER');
            $table->integer('urutan')->default(1);
            $table->integer('icd_id')->nullable();
            $table->integer('tindakan_id')->nullable();
            $table->text('keterangan')->nullable();

            $table->index('registrasi_id');
            $table->index(['jenis', 'tipe']);
        });

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

        Schema::create('bridging_log', function (Blueprint $table) {
            $table->increments('bridging_log_id');
            $table->string('jenis', 30);
            $table->string('endpoint', 150)->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->integer('status_code')->nullable();
            $table->text('keterangan')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamp('waktu')->nullable();

            $table->index('jenis');
            $table->index('waktu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bridging_log');
        Schema::dropIfExists('ina_cbg_result');
        Schema::dropIfExists('klaim_log');
        Schema::dropIfExists('klaim_verifikasi');
        Schema::dropIfExists('klaim_berkas');
        Schema::dropIfExists('klaim_detail');
        Schema::dropIfExists('klaim');
        Schema::dropIfExists('diagnosa_prosedur');
        Schema::dropIfExists('kode_eksternal');
    }
};
