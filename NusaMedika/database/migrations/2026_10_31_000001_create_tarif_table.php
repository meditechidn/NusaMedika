<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ganti tabel `tindakan_harga` dengan tabel `tarif`.
 *
 * `tindakan_harga` awalnya membawa banyak dimensi (nasabah, bagian, profesi,
 * jabatan, jenis rawat, jenis kamar, periode berlaku) yang tidak pernah diisi
 * aplikasi. Master tarif yang dibutuhkan hanyalah: satu tarif default per
 * tindakan + satu tarif per kelas perawatan (Kelas 1/2/3/VIP/VVIP).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarif', function (Blueprint $table) {
            $table->increments('tarif_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();

            $table->integer('tindakan_id');
            $table->integer('kelas_ruang_id')->nullable(); // NULL = tarif default
            $table->decimal('tarif', 15, 2)->default(0);
            $table->decimal('jasa_sarana', 15, 2)->nullable()->default(0);
            $table->decimal('jasa_medis', 15, 2)->nullable()->default(0);
            $table->decimal('bhp', 15, 2)->nullable()->default(0);

            $table->index('tindakan_id');
            $table->index('kelas_ruang_id');
        });

        $this->pindahkanData();

        Schema::dropIfExists('tindakan_harga');
        Schema::dropIfExists('tarif_penjamin');
    }

    public function down(): void
    {
        Schema::create('tindakan_harga', function (Blueprint $table) {
            $table->increments('tindakan_harga_id');
            $table->timestamp('input_time', 6)->nullable();
            $table->integer('input_user_id')->nullable();
            $table->timestamp('mod_time', 6)->nullable();
            $table->integer('mod_user_id')->nullable();
            $table->smallInteger('status_batal')->nullable();
            $table->integer('tindakan_id');
            $table->integer('kelas_ruang_id')->nullable();
            $table->decimal('tarif', 15, 2)->default(0);
            $table->decimal('jasa_sarana', 15, 2)->nullable()->default(0);
            $table->decimal('jasa_medis', 15, 2)->nullable()->default(0);
            $table->decimal('bhp', 15, 2)->nullable()->default(0);
            $table->index('tindakan_id');
        });

        DB::table('tindakan_harga')->insert(
            DB::table('tarif')
                ->orderBy('tarif_id')
                ->get([
                    'tindakan_id', 'kelas_ruang_id', 'tarif', 'jasa_sarana', 'jasa_medis', 'bhp',
                    'input_time', 'input_user_id', 'mod_time', 'mod_user_id', 'status_batal',
                ])
                ->map(fn ($row) => (array) $row)
                ->all()
        );

        Schema::dropIfExists('tarif');
    }

    /**
     * Salin tarif aktif dari `tindakan_harga` ke `tarif`. Dimensi lama
     * (jenis rawat / jenis kamar / penjamin) dibuang; satu baris per
     * (tindakan, kelas) dengan baris terbaru menang.
     */
    private function pindahkanData(): void
    {
        if (! Schema::hasTable('tindakan_harga')) {
            return;
        }

        $terpakai = [];

        DB::table('tindakan_harga')
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->orderByDesc('tindakan_harga_id')
            ->get()
            ->each(function ($row) use (&$terpakai) {
                $kunci = $row->tindakan_id.'|'.($row->kelas_ruang_id ?? 'default');

                if (isset($terpakai[$kunci])) {
                    return;
                }

                $terpakai[$kunci] = true;

                DB::table('tarif')->insert([
                    'tindakan_id' => $row->tindakan_id,
                    'kelas_ruang_id' => $row->kelas_ruang_id,
                    'tarif' => $row->tarif ?? 0,
                    'jasa_sarana' => $row->jasa_sarana ?? 0,
                    'jasa_medis' => $row->jasa_medis ?? 0,
                    'bhp' => $row->bhp ?? 0,
                    'input_time' => $row->input_time,
                    'input_user_id' => $row->input_user_id,
                    'mod_time' => $row->mod_time,
                    'mod_user_id' => $row->mod_user_id,
                    'status_batal' => $row->status_batal ?? 0,
                ]);
            });
    }
};
