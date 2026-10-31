<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
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
            $table->integer('registrasi_id')->index();
            $table->integer('pasien_id')->index();
            $table->integer('pasien_nasabah_id')->nullable();
            $table->integer('nasabah_id')->nullable();
            $table->string('jenis_rawat', 3)->nullable();
            $table->integer('kelas_ruang_id')->nullable();
            $table->integer('hak_kelas_ruang_id')->nullable();
            $table->string('jenis_billing', 20)->default('FEE_FOR_SERVICE');
            $table->smallInteger('status_billing')->default(0)->index();
            $table->timestamp('tgl_billing')->nullable()->index();
            $table->timestamp('tgl_masuk')->nullable();
            $table->timestamp('tgl_keluar')->nullable();
            $table->string('sep', 30)->nullable();
            $table->decimal('subtotal', 15)->default(0);
            $table->decimal('diskon', 15)->default(0);
            $table->decimal('pajak', 15)->default(0);
            $table->decimal('service', 15)->default(0);
            $table->decimal('materai', 15)->default(0);
            $table->decimal('pembulatan', 15)->default(0);
            $table->decimal('total', 15)->default(0);
            $table->decimal('jaminan_penjamin', 15)->default(0);
            $table->decimal('tanggungan_pasien', 15)->default(0);
            $table->decimal('dibayar', 15)->default(0);
            $table->decimal('sisa', 15)->default(0);
            $table->decimal('deposit_terpakai', 15)->default(0);
            $table->string('no_closing', 30)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->integer('closed_by')->nullable();
            $table->timestamp('void_at')->nullable();
            $table->integer('void_by')->nullable();
            $table->text('void_reason')->nullable();
            $table->text('catatan')->nullable();
            $table->decimal('write_off', 15)->default(0);
            $table->text('write_off_reason')->nullable();
            $table->timestamp('write_off_at')->nullable();
            $table->integer('write_off_by')->nullable();
            $table->smallInteger('status_selesai')->default(0);
            $table->integer('registrasi_detail_id')->nullable()->index();
        });

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS billing_registrasi_detail_aktif_unique ON billing (registrasi_detail_id) WHERE (status_batal IS NULL) OR (status_batal = 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS billing_registrasi_detail_aktif_unique');
        DB::statement('DROP INDEX IF EXISTS billing_registrasi_detail_aktif_unique');
        Schema::dropIfExists('billing');
    }
};
