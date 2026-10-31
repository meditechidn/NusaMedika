<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarif_penjamin', function (Blueprint $table) {
            $table->increments('tarif_penjamin_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->integer('nasabah_id');
            $table->integer('tindakan_id');
            $table->integer('kelas_ruang_id')->nullable();
            $table->decimal('tarif', 15, 2)->default(0);
            $table->decimal('jasa_sarana', 15, 2)->default(0);
            $table->decimal('jasa_medis', 15, 2)->default(0);
            $table->decimal('bhp', 15, 2)->default(0);
            $table->date('tanggal_berlaku')->nullable();
            $table->date('tanggal_akhir')->nullable();
            $table->text('keterangan')->nullable();

            $table->index('nasabah_id');
            $table->index('tindakan_id');
        });

        Schema::create('barang_harga', function (Blueprint $table) {
            $table->increments('barang_harga_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->integer('barang_id');
            $table->integer('nasabah_id')->nullable();
            $table->integer('kelas_ruang_id')->nullable();
            $table->decimal('harga_beli', 15, 2)->nullable();
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->decimal('margin_persen', 5, 2)->default(0);
            $table->date('tanggal_berlaku')->nullable();
            $table->date('tanggal_akhir')->nullable();
            $table->smallInteger('is_aktif')->default(1);
            $table->text('keterangan')->nullable();

            $table->index('barang_id');
            $table->index('nasabah_id');
        });

        Schema::create('kamar_harga', function (Blueprint $table) {
            $table->increments('kamar_harga_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->integer('bagian_id')->nullable();
            $table->integer('kelas_ruang_id')->nullable();
            $table->integer('nasabah_id')->nullable();
            $table->string('jenis_kamar', 30)->nullable();
            $table->decimal('tarif', 15, 2)->default(0);
            $table->date('tanggal_berlaku')->nullable();
            $table->date('tanggal_akhir')->nullable();
            $table->text('keterangan')->nullable();

            $table->index('bagian_id');
            $table->index('kelas_ruang_id');
        });

        Schema::create('administrasi_harga', function (Blueprint $table) {
            $table->increments('administrasi_harga_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->string('jenis_rawat', 3);
            $table->integer('kelas_ruang_id')->nullable();
            $table->integer('nasabah_id')->nullable();
            $table->decimal('tarif', 15, 2)->default(0);
            $table->date('tanggal_berlaku')->nullable();
            $table->date('tanggal_akhir')->nullable();
            $table->text('keterangan')->nullable();

            $table->index('jenis_rawat');
        });

        Schema::create('visite_harga', function (Blueprint $table) {
            $table->increments('visite_harga_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->integer('profesi_id')->nullable();
            $table->integer('jabatan_id')->nullable();
            $table->integer('kelas_ruang_id')->nullable();
            $table->integer('nasabah_id')->nullable();
            $table->string('jenis_rawat', 3)->nullable();
            $table->decimal('tarif', 15, 2)->default(0);
            $table->date('tanggal_berlaku')->nullable();
            $table->date('tanggal_akhir')->nullable();
            $table->text('keterangan')->nullable();

            $table->index('profesi_id');
            $table->index('jenis_rawat');
        });

        Schema::create('paket_tindakan', function (Blueprint $table) {
            $table->increments('paket_tindakan_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->string('kode_paket', 30)->unique();
            $table->string('nama_paket', 150);
            $table->string('jenis_rawat', 3)->nullable();
            $table->integer('kelas_ruang_id')->nullable();
            $table->integer('nasabah_id')->nullable();
            $table->decimal('harga_paket', 15, 2)->default(0);
            $table->smallInteger('is_aktif')->default(1);
            $table->text('keterangan')->nullable();
        });

        Schema::create('paket_tindakan_detail', function (Blueprint $table) {
            $table->increments('paket_tindakan_detail_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->integer('paket_tindakan_id');
            $table->integer('tindakan_id');
            $table->decimal('qty', 15, 2)->default(1);
            $table->decimal('harga_komponen', 15, 2)->default(0);

            $table->index('paket_tindakan_id');
            $table->index('tindakan_id');
        });

        Schema::create('komponen_biaya', function (Blueprint $table) {
            $table->increments('komponen_biaya_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->string('kode', 30)->unique();
            $table->string('nama', 150);
            $table->string('kategori', 30)->nullable();
            $table->string('satuan', 20)->nullable();
            $table->decimal('harga_default', 15, 2)->default(0);
            $table->smallInteger('is_aktif')->default(1);
            $table->text('keterangan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('komponen_biaya');
        Schema::dropIfExists('paket_tindakan_detail');
        Schema::dropIfExists('paket_tindakan');
        Schema::dropIfExists('visite_harga');
        Schema::dropIfExists('administrasi_harga');
        Schema::dropIfExists('kamar_harga');
        Schema::dropIfExists('barang_harga');
        Schema::dropIfExists('tarif_penjamin');
    }
};
