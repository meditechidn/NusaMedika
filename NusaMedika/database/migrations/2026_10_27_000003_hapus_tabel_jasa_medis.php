<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('jasa_medis_rekap');
        Schema::dropIfExists('jasa_medis_tenaga_map');
        Schema::dropIfExists('jasa_medis_distribusi');
        Schema::dropIfExists('jasa_medis_rule');
    }

    public function down(): void
    {
        if (! Schema::hasTable('jasa_medis_rule')) {
            Schema::create('jasa_medis_rule', function (Blueprint $table) {
                $table->increments('jasa_medis_rule_id');
                $table->timestamp('input_time', 6)->nullable();
                $table->integer('input_user_id')->nullable();
                $table->timestamp('mod_time', 6)->nullable();
                $table->integer('mod_user_id')->nullable();
                $table->smallInteger('status_batal')->nullable();

                $table->string('kode_rule', 30)->unique();
                $table->string('nama_rule', 150);
                $table->string('sumber', 30);
                $table->integer('sumber_id')->nullable();
                $table->integer('kategori_tindakan_id')->nullable();
                $table->integer('group_tindakan_id')->nullable();
                $table->integer('paket_tindakan_id')->nullable();
                $table->integer('profesi_id')->nullable();
                $table->integer('jabatan_id')->nullable();
                $table->string('peran', 30)->nullable();
                $table->string('jenis_nilai', 20)->default('PERSEN');
                $table->string('basis', 30)->default('TARIF_TINDAKAN');
                $table->decimal('nilai', 15, 2)->default(0);
                $table->decimal('min_nilai', 15, 2)->nullable();
                $table->decimal('max_nilai', 15, 2)->nullable();
                $table->integer('prioritas')->default(1);
                $table->integer('kelas_ruang_id')->nullable();
                $table->integer('nasabah_id')->nullable();
                $table->string('jenis_rawat', 3)->nullable();
                $table->date('tanggal_berlaku')->nullable();
                $table->date('tanggal_akhir')->nullable();
                $table->smallInteger('is_aktif')->default(1);
                $table->text('keterangan')->nullable();

                $table->index('sumber');
                $table->index('profesi_id');
                $table->index('prioritas');
            });
        }

        if (! Schema::hasTable('jasa_medis_distribusi')) {
            Schema::create('jasa_medis_distribusi', function (Blueprint $table) {
                $table->increments('jasa_medis_distribusi_id');
                $table->timestamp('input_time', 6)->nullable();
                $table->integer('input_user_id')->nullable();
                $table->timestamp('mod_time', 6)->nullable();
                $table->integer('mod_user_id')->nullable();
                $table->smallInteger('status_batal')->nullable();

                $table->integer('jasa_medis_rule_id');
                $table->integer('profesi_id')->nullable();
                $table->integer('jabatan_id')->nullable();
                $table->string('peran', 30)->nullable();
                $table->decimal('persen', 5, 2)->default(0);
                $table->decimal('nominal', 15, 2)->default(0);
                $table->integer('prioritas')->default(1);

                $table->index('jasa_medis_rule_id');
            });
        }

        if (! Schema::hasTable('jasa_medis_tenaga_map')) {
            Schema::create('jasa_medis_tenaga_map', function (Blueprint $table) {
                $table->increments('jasa_medis_tenaga_map_id');
                $table->timestamp('input_time', 6)->nullable();
                $table->integer('input_user_id')->nullable();
                $table->timestamp('mod_time', 6)->nullable();
                $table->integer('mod_user_id')->nullable();
                $table->smallInteger('status_batal')->nullable();

                $table->string('sumber', 30);
                $table->string('form_slug', 80)->nullable();
                $table->string('variabel', 80);
                $table->string('peran', 30)->default('DOKTER');
                $table->integer('profesi_id')->nullable();
                $table->smallInteger('is_aktif')->default(1);
                $table->text('keterangan')->nullable();

                $table->index('sumber');
            });
        }

        if (! Schema::hasTable('jasa_medis_rekap')) {
            Schema::create('jasa_medis_rekap', function (Blueprint $table) {
                $table->increments('jasa_medis_rekap_id');
                $table->timestamp('input_time', 6)->nullable();
                $table->integer('input_user_id')->nullable();
                $table->timestamp('mod_time', 6)->nullable();
                $table->integer('mod_user_id')->nullable();
                $table->smallInteger('status_batal')->nullable();

                $table->integer('billing_id');
                $table->integer('pegawai_id');
                $table->integer('profesi_id')->nullable();
                $table->string('peran', 30)->nullable();
                $table->string('jenis_rawat', 3)->nullable();
                $table->string('periode', 10)->nullable();
                $table->date('tanggal')->nullable();
                $table->decimal('total_jasa', 15, 2)->default(0);
                $table->integer('total_tindakan')->default(0);
                $table->string('status', 20)->default('OPEN');
                $table->text('keterangan')->nullable();

                $table->index('billing_id');
                $table->index('pegawai_id');
                $table->index('periode');
            });
        }
    }
};
