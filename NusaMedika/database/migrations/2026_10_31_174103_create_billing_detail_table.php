<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('billing_detail', function (Blueprint $table) {
            $table->increments('billing_detail_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->integer('billing_id')->index();
            $table->integer('registrasi_detail_id')->nullable()->index();
            $table->string('kode_unik', 120)->unique();
            $table->date('tanggal')->nullable();
            $table->string('jenis_layanan', 30)->index();
            $table->string('sumber_type', 30)->nullable();
            $table->integer('sumber_id')->nullable();
            $table->integer('ref_detail_id')->nullable();
            $table->string('kode', 50)->nullable();
            $table->string('nama');
            $table->string('satuan', 20)->nullable();
            $table->decimal('qty', 15)->default(1);
            $table->decimal('harga_satuan', 15)->default(0);
            $table->decimal('harga_beli', 15)->default(0);
            $table->decimal('diskon_persen', 5)->default(0);
            $table->decimal('diskon_nominal', 15)->default(0);
            $table->decimal('subtotal', 15)->default(0);
            $table->decimal('jasa_sarana', 15)->default(0);
            $table->decimal('jasa_medis', 15)->default(0);
            $table->decimal('bhp', 15)->default(0);
            $table->decimal('pajak', 15)->default(0);
            $table->decimal('service', 15)->default(0);
            $table->decimal('penjamin_share', 15)->default(0);
            $table->decimal('pasien_share', 15)->default(0);
            $table->smallInteger('is_covered')->default(1);
            $table->string('alasan_tidak_coverage')->nullable();
            $table->smallInteger('kena_pajak')->default(0);
            $table->smallInteger('kena_service')->default(0);
            $table->smallInteger('status')->default(0)->index();
            $table->string('void_reason')->nullable();
            $table->integer('bagian_id')->nullable();
            $table->integer('kelas_ruang_id')->nullable();
            $table->integer('dokter_id')->nullable();
            $table->integer('profesi_id')->nullable();
            $table->smallInteger('flag_cito')->default(0);
            $table->string('kode_bpjs', 30)->nullable();
            $table->string('kode_inacbg', 30)->nullable();
            $table->string('kode_icd9', 20)->nullable();

            $table->index(['sumber_type', 'sumber_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_detail');
    }
};
