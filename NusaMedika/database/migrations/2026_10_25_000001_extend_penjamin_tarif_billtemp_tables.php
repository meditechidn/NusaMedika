<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nasabah', function (Blueprint $table) {
            if (! Schema::hasColumn('nasabah', 'kode_penjamin')) {
                $table->string('kode_penjamin', 30)->nullable()->after('nama_nasabah');
            }
            if (! Schema::hasColumn('nasabah', 'jenis_penjamin')) {
                $table->string('jenis_penjamin', 20)->nullable()->default('UMUM')->after('kode_penjamin');
            }
            if (! Schema::hasColumn('nasabah', 'is_aktif')) {
                $table->smallInteger('is_aktif')->nullable()->default(1)->after('jenis_penjamin');
            }
            if (! Schema::hasColumn('nasabah', 'logo_url')) {
                $table->string('logo_url', 255)->nullable()->after('is_aktif');
            }
            if (! Schema::hasColumn('nasabah', 'catatan')) {
                $table->text('catatan')->nullable()->after('logo_url');
            }
        });

        Schema::table('pasien_nasabah', function (Blueprint $table) {
            if (! Schema::hasColumn('pasien_nasabah', 'is_primary')) {
                $table->smallInteger('is_primary')->nullable()->default(0)->after('hak_kelas_id');
            }
            if (! Schema::hasColumn('pasien_nasabah', 'tanggal_mulai')) {
                $table->date('tanggal_mulai')->nullable()->after('is_primary');
            }
            if (! Schema::hasColumn('pasien_nasabah', 'tanggal_akhir')) {
                $table->date('tanggal_akhir')->nullable()->after('tanggal_mulai');
            }
            if (! Schema::hasColumn('pasien_nasabah', 'co_pay_persen')) {
                $table->decimal('co_pay_persen', 5, 2)->nullable()->default(0)->after('tanggal_akhir');
            }
            if (! Schema::hasColumn('pasien_nasabah', 'limit_jaminan')) {
                $table->decimal('limit_jaminan', 15, 2)->nullable()->after('co_pay_persen');
            }
            if (! Schema::hasColumn('pasien_nasabah', 'status_verifikasi')) {
                $table->string('status_verifikasi', 20)->nullable()->after('limit_jaminan');
            }
            if (! Schema::hasColumn('pasien_nasabah', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('status_verifikasi');
            }
            if (! Schema::hasColumn('pasien_nasabah', 'verified_by')) {
                $table->integer('verified_by')->nullable()->after('verified_at');
            }
        });

        Schema::table('rujukan_sep', function (Blueprint $table) {
            foreach ([
                'jenis_pelayanan' => ['string', 20],
                'kelas_rawat' => ['string', 20],
                'tgl_sep' => ['date', null],
                'tgl_rujukan' => ['date', null],
                'diagnosa_awal' => ['string', 10],
                'jenis_peserta' => ['string', 30],
                'no_kartu' => ['string', 30],
                'tujuan_kunjungan' => ['string', 30],
                'no_skdp' => ['string', 30],
                'status' => ['string', 20],
            ] as $kolom => [$tipe, $panjang]) {
                if (! Schema::hasColumn('rujukan_sep', $kolom)) {
                    $table->{$tipe}($kolom, ...($panjang ? [$panjang] : []))->nullable();
                }
            }
            if (! Schema::hasColumn('rujukan_sep', 'flag_prosedur')) {
                $table->smallInteger('flag_prosedur')->nullable();
            }
            if (! Schema::hasColumn('rujukan_sep', 'dpjp_id')) {
                $table->integer('dpjp_id')->nullable();
            }
            if (! Schema::hasColumn('rujukan_sep', 'catatan')) {
                $table->text('catatan')->nullable();
            }
        });

        Schema::table('tindakan_harga', function (Blueprint $table) {
            if (! Schema::hasColumn('tindakan_harga', 'jasa_sarana')) {
                $table->decimal('jasa_sarana', 15, 2)->nullable()->default(0)->after('tarif');
            }
            if (! Schema::hasColumn('tindakan_harga', 'jasa_medis')) {
                $table->decimal('jasa_medis', 15, 2)->nullable()->default(0)->after('jasa_sarana');
            }
            if (! Schema::hasColumn('tindakan_harga', 'bhp')) {
                $table->decimal('bhp', 15, 2)->nullable()->default(0)->after('jasa_medis');
            }
            if (! Schema::hasColumn('tindakan_harga', 'diskon_persen')) {
                $table->decimal('diskon_persen', 5, 2)->nullable()->default(0)->after('bhp');
            }
            if (! Schema::hasColumn('tindakan_harga', 'tanggal_berlaku')) {
                $table->date('tanggal_berlaku')->nullable()->after('diskon_persen');
            }
            if (! Schema::hasColumn('tindakan_harga', 'tanggal_akhir')) {
                $table->date('tanggal_akhir')->nullable()->after('tanggal_berlaku');
            }
            if (! Schema::hasColumn('tindakan_harga', 'keterangan')) {
                $table->text('keterangan')->nullable()->after('tanggal_akhir');
            }
        });

        Schema::table('bill_temp', function (Blueprint $table) {
            if (! Schema::hasColumn('bill_temp', 'billing_id')) {
                $table->integer('billing_id')->nullable()->after('bill_temp_id');
                $table->index('billing_id');
            }
        });

        Schema::table('kartu_stock', function (Blueprint $table) {
            if (! Schema::hasColumn('kartu_stock', 'ref_emr_id')) {
                $table->integer('ref_emr_id')->nullable()->after('ref_peresepan_obat_detail_id');
                $table->index('ref_emr_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kartu_stock', function (Blueprint $table) {
            if (Schema::hasColumn('kartu_stock', 'ref_emr_id')) {
                $table->dropColumn('ref_emr_id');
            }
        });

        Schema::table('bill_temp', function (Blueprint $table) {
            if (Schema::hasColumn('bill_temp', 'billing_id')) {
                $table->dropColumn('billing_id');
            }
        });

        Schema::table('tindakan_harga', function (Blueprint $table) {
            foreach (['jasa_sarana', 'jasa_medis', 'bhp', 'diskon_persen', 'tanggal_berlaku', 'tanggal_akhir', 'keterangan'] as $kolom) {
                if (Schema::hasColumn('tindakan_harga', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });

        Schema::table('rujukan_sep', function (Blueprint $table) {
            foreach (['jenis_pelayanan', 'kelas_rawat', 'tgl_sep', 'tgl_rujukan', 'diagnosa_awal', 'jenis_peserta', 'no_kartu', 'tujuan_kunjungan', 'flag_prosedur', 'dpjp_id', 'no_skdp', 'status', 'catatan'] as $kolom) {
                if (Schema::hasColumn('rujukan_sep', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });

        Schema::table('pasien_nasabah', function (Blueprint $table) {
            foreach (['is_primary', 'tanggal_mulai', 'tanggal_akhir', 'co_pay_persen', 'limit_jaminan', 'status_verifikasi', 'verified_at', 'verified_by'] as $kolom) {
                if (Schema::hasColumn('pasien_nasabah', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });

        Schema::table('nasabah', function (Blueprint $table) {
            foreach (['kode_penjamin', 'jenis_penjamin', 'is_aktif', 'logo_url', 'catatan'] as $kolom) {
                if (Schema::hasColumn('nasabah', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
