<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing', function (Blueprint $table) {
            $table->increments('billing_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->string('no_billing', 30)->nullable()->unique();
            $table->integer('registrasi_id');
            $table->integer('pasien_id');
            $table->integer('pasien_nasabah_id')->nullable();
            $table->integer('nasabah_id')->nullable();
            $table->string('jenis_rawat', 3)->nullable();
            $table->integer('kelas_ruang_id')->nullable();
            $table->integer('hak_kelas_ruang_id')->nullable();
            $table->string('jenis_billing', 20)->default('FEE_FOR_SERVICE');
            $table->smallInteger('status_billing')->default(0);
            $table->timestamp('tgl_billing')->nullable();
            $table->timestamp('tgl_masuk')->nullable();
            $table->timestamp('tgl_keluar')->nullable();
            $table->string('sep', 30)->nullable();

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('pajak', 15, 2)->default(0);
            $table->decimal('service', 15, 2)->default(0);
            $table->decimal('materai', 15, 2)->default(0);
            $table->decimal('pembulatan', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('jaminan_penjamin', 15, 2)->default(0);
            $table->decimal('tanggungan_pasien', 15, 2)->default(0);
            $table->decimal('dibayar', 15, 2)->default(0);
            $table->decimal('sisa', 15, 2)->default(0);
            $table->decimal('deposit_terpakai', 15, 2)->default(0);

            $table->string('no_closing', 30)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->integer('closed_by')->nullable();
            $table->timestamp('void_at')->nullable();
            $table->integer('void_by')->nullable();
            $table->text('void_reason')->nullable();
            $table->text('catatan')->nullable();

            $table->index('registrasi_id');
            $table->index('pasien_id');
            $table->index('status_billing');
            $table->index('tgl_billing');
        });

        Schema::create('billing_detail', function (Blueprint $table) {
            $table->increments('billing_detail_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->integer('billing_id');
            $table->integer('registrasi_detail_id')->nullable();
            $table->integer('bill_temp_id')->nullable();
            $table->string('kode_unik', 120)->unique();
            $table->date('tanggal')->nullable();
            $table->string('jenis_layanan', 30);
            $table->string('sumber_type', 30)->nullable();
            $table->integer('sumber_id')->nullable();
            $table->integer('ref_detail_id')->nullable();
            $table->string('kode', 50)->nullable();
            $table->string('nama', 255);
            $table->string('satuan', 20)->nullable();
            $table->decimal('qty', 15, 2)->default(1);
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('diskon_persen', 5, 2)->default(0);
            $table->decimal('diskon_nominal', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('jasa_sarana', 15, 2)->default(0);
            $table->decimal('jasa_medis', 15, 2)->default(0);
            $table->decimal('bhp', 15, 2)->default(0);
            $table->decimal('pajak', 15, 2)->default(0);
            $table->decimal('service', 15, 2)->default(0);
            $table->decimal('penjamin_share', 15, 2)->default(0);
            $table->decimal('pasien_share', 15, 2)->default(0);
            $table->smallInteger('is_covered')->default(1);
            $table->string('alasan_tidak_coverage', 255)->nullable();
            $table->smallInteger('kena_pajak')->default(0);
            $table->smallInteger('kena_service')->default(0);
            $table->smallInteger('status')->default(0);
            $table->string('void_reason', 255)->nullable();
            $table->integer('bagian_id')->nullable();
            $table->integer('kelas_ruang_id')->nullable();
            $table->integer('dokter_id')->nullable();
            $table->integer('profesi_id')->nullable();
            $table->smallInteger('flag_cito')->default(0);
            $table->string('kode_bpjs', 30)->nullable();
            $table->string('kode_inacbg', 30)->nullable();
            $table->string('kode_icd9', 20)->nullable();

            $table->index('billing_id');
            $table->index('registrasi_detail_id');
            $table->index('jenis_layanan');
            $table->index('status');
            $table->index(['sumber_type', 'sumber_id']);
        });

        Schema::create('billing_tenaga', function (Blueprint $table) {
            $table->increments('billing_tenaga_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->integer('billing_detail_id');
            $table->integer('pegawai_id');
            $table->integer('profesi_id')->nullable();
            $table->string('peran', 30);
            $table->decimal('persen', 5, 2)->default(0);
            $table->decimal('nominal', 15, 2)->default(0);
            $table->string('sumber', 30)->nullable();

            $table->index('billing_detail_id');
            $table->index('pegawai_id');
        });

        Schema::create('billing_penjamin', function (Blueprint $table) {
            $table->increments('billing_penjamin_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->integer('billing_id');
            $table->integer('pasien_nasabah_id')->nullable();
            $table->integer('nasabah_id')->nullable();
            $table->integer('urutan')->default(1);
            $table->smallInteger('is_primary')->default(0);
            $table->decimal('share_persen', 5, 2)->default(100);
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->string('jenis_jaminan', 20)->nullable();

            $table->index('billing_id');
        });

        Schema::create('billing_log', function (Blueprint $table) {
            $table->increments('billing_log_id');
            $table->integer('billing_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('aksi', 50);
            $table->string('entitas', 40)->nullable();
            $table->integer('entitas_id')->nullable();
            $table->json('data_lama')->nullable();
            $table->json('data_baru')->nullable();
            $table->text('alasan')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('waktu')->nullable();

            $table->index('billing_id');
            $table->index('aksi');
            $table->index('waktu');
        });

        Schema::create('billing_counter', function (Blueprint $table) {
            $table->increments('billing_counter_id');
            $table->string('jenis', 20);
            $table->string('periode', 10);
            $table->integer('last_number')->default(0);

            $table->unique(['jenis', 'periode']);
        });

        Schema::create('billing_pengaturan', function (Blueprint $table) {
            $table->increments('billing_pengaturan_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->string('kunci', 50)->unique();
            $table->text('nilai')->nullable();
            $table->string('tipe', 20)->default('string');
            $table->string('grup', 30)->default('umum');
            $table->string('keterangan', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_pengaturan');
        Schema::dropIfExists('billing_counter');
        Schema::dropIfExists('billing_log');
        Schema::dropIfExists('billing_penjamin');
        Schema::dropIfExists('billing_tenaga');
        Schema::dropIfExists('billing_detail');
        Schema::dropIfExists('billing');
    }
};
