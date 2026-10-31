<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_pembayaran', function (Blueprint $table) {
            $table->increments('billing_pembayaran_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->integer('billing_id');
            $table->string('no_kwitansi', 30)->nullable()->unique();
            $table->timestamp('tanggal')->nullable();
            $table->string('metode', 20);
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->decimal('kembalian', 15, 2)->default(0);
            $table->string('referensi', 100)->nullable();
            $table->string('bank', 50)->nullable();
            $table->integer('kasir_user_id')->nullable();
            $table->integer('kasir_shift_id')->nullable();
            $table->smallInteger('status')->default(1);
            $table->text('void_reason')->nullable();

            $table->index('billing_id');
            $table->index('tanggal');
            $table->index('status');
        });

        Schema::create('billing_pembayaran_detail', function (Blueprint $table) {
            $table->increments('billing_pembayaran_detail_id');
            $table->integer('billing_pembayaran_id');
            $table->integer('billing_detail_id')->nullable();
            $table->integer('billing_penjamin_id')->nullable();
            $table->decimal('jumlah', 15, 2)->default(0);

            $table->index('billing_pembayaran_id');
            $table->index('billing_detail_id');
        });

        Schema::create('billing_deposit', function (Blueprint $table) {
            $table->increments('billing_deposit_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->integer('pasien_id')->unique();
            $table->decimal('saldo', 15, 2)->default(0);
            $table->string('status', 20)->default('AKTIF');
        });

        Schema::create('billing_deposit_mutasi', function (Blueprint $table) {
            $table->increments('billing_deposit_mutasi_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->integer('billing_deposit_id');
            $table->integer('billing_id')->nullable();
            $table->string('jenis', 20);
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->decimal('saldo_sebelum', 15, 2)->default(0);
            $table->decimal('saldo_sesudah', 15, 2)->default(0);
            $table->string('referensi', 100)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamp('tanggal')->nullable();

            $table->index('billing_deposit_id');
            $table->index('billing_id');
        });

        Schema::create('billing_piutang', function (Blueprint $table) {
            $table->increments('billing_piutang_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->integer('billing_id');
            $table->integer('penjamin_id');
            $table->string('no_invoice', 30)->nullable()->unique();
            $table->date('tanggal')->nullable();
            $table->date('jatuh_tempo')->nullable();
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->decimal('dibayar', 15, 2)->default(0);
            $table->decimal('sisa', 15, 2)->default(0);
            $table->string('status', 20)->default('OPEN');
            $table->text('keterangan')->nullable();

            $table->index('billing_id');
            $table->index('penjamin_id');
            $table->index('status');
        });

        Schema::create('billing_piutang_pembayaran', function (Blueprint $table) {
            $table->increments('billing_piutang_pembayaran_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->integer('billing_piutang_id');
            $table->date('tanggal')->nullable();
            $table->string('metode', 20);
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->string('referensi', 100)->nullable();
            $table->text('keterangan')->nullable();

            $table->index('billing_piutang_id');
        });

        Schema::create('kasir_shift', function (Blueprint $table) {
            $table->increments('kasir_shift_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->integer('kasir_user_id');
            $table->timestamp('waktu_buka')->nullable();
            $table->timestamp('waktu_tutup')->nullable();
            $table->decimal('kas_awal', 15, 2)->default(0);
            $table->decimal('kas_akhir', 15, 2)->default(0);
            $table->decimal('total_penerimaan', 15, 2)->default(0);
            $table->decimal('total_pengeluaran', 15, 2)->default(0);
            $table->decimal('selisih', 15, 2)->default(0);
            $table->string('status', 20)->default('OPEN');
            $table->text('catatan')->nullable();

            $table->index('kasir_user_id');
            $table->index('status');
        });

        Schema::create('kasir_shift_denominasi', function (Blueprint $table) {
            $table->increments('kasir_shift_denominasi_id');
            $table->integer('kasir_shift_id');
            $table->decimal('pecahan', 15, 2)->default(0);
            $table->integer('jumlah_lembar')->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);

            $table->index('kasir_shift_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kasir_shift_denominasi');
        Schema::dropIfExists('kasir_shift');
        Schema::dropIfExists('billing_piutang_pembayaran');
        Schema::dropIfExists('billing_piutang');
        Schema::dropIfExists('billing_deposit_mutasi');
        Schema::dropIfExists('billing_deposit');
        Schema::dropIfExists('billing_pembayaran_detail');
        Schema::dropIfExists('billing_pembayaran');
    }
};
