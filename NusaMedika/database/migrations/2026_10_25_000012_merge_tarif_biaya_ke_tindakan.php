<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $kategori = [
        'Administrasi' => 'Administrasi',
        'Kamar' => 'Kamar',
        'Makanan' => 'Makanan',
        'Visite' => 'Visite',
        'Konsultasi' => 'Konsultasi',
        'Emergency' => 'Emergency',
        'MCU' => 'MCU',
        'Lainnya' => 'Lainnya',
    ];

    private array $master = [
        'ADMINISTRASI' => ['Biaya Administrasi', 'Administrasi'],
        'KAMAR' => ['Kamar Rawat Inap', 'Kamar'],
        'MAKANAN' => ['Biaya Makanan', 'Makanan'],
        'VISITE' => ['Visite Dokter', 'Visite'],
        'KONSULTASI' => ['Jasa Konsultasi', 'Konsultasi'],
        'EMERGENCY' => ['Tindakan Emergency', 'Emergency'],
        'MCU' => ['Medical Checkup', 'MCU'],
        'LAINNYA' => ['Biaya Lainnya', 'Lainnya'],
    ];

    public function up(): void
    {
        Schema::table('tindakan_harga', function (Blueprint $table) {
            if (! Schema::hasColumn('tindakan_harga', 'nasabah_id')) {
                $table->integer('nasabah_id')->nullable()->after('kelas_ruang_id');
            }
            if (! Schema::hasColumn('tindakan_harga', 'bagian_id')) {
                $table->integer('bagian_id')->nullable()->after('nasabah_id');
            }
            if (! Schema::hasColumn('tindakan_harga', 'profesi_id')) {
                $table->integer('profesi_id')->nullable()->after('bagian_id');
            }
            if (! Schema::hasColumn('tindakan_harga', 'jabatan_id')) {
                $table->integer('jabatan_id')->nullable()->after('profesi_id');
            }
            if (! Schema::hasColumn('tindakan_harga', 'jenis_rawat')) {
                $table->string('jenis_rawat', 3)->nullable()->after('jabatan_id');
            }
            if (! Schema::hasColumn('tindakan_harga', 'jenis_kamar')) {
                $table->string('jenis_kamar', 30)->nullable()->after('jenis_rawat');
            }
        });

        $uniqueLama = collect(Schema::getIndexes('tindakan_harga'))
            ->contains(fn (array $index) => ($index['name'] ?? null) === 'tindakan_harga_tindakan_id_kelas_ruang_id_unique');

        if ($uniqueLama) {
            Schema::table('tindakan_harga', function (Blueprint $table) {
                $table->dropUnique('tindakan_harga_tindakan_id_kelas_ruang_id_unique');
            });
        }

        $now = now();

        foreach ($this->kategori as $nama) {
            DB::table('kategori_tindakan')->updateOrInsert(
                ['nama_kategori_tindakan' => $nama],
                [
                    'input_time' => $now,
                    'input_user_id' => 1,
                    'status_batal' => 0,
                ]
            );
        }

        $kategoriIds = DB::table('kategori_tindakan')
            ->whereIn('nama_kategori_tindakan', array_values($this->kategori))
            ->pluck('kategori_tindakan_id', 'nama_kategori_tindakan');

        foreach ($this->master as $kode => [$nama, $kategori]) {
            DB::table('tindakan')->updateOrInsert(
                ['kode_tindakan' => $kode],
                [
                    'nama_tindakan' => $nama,
                    'kategori_tindakan_id' => $kategoriIds[$kategori] ?? null,
                    'input_time' => $now,
                    'input_user_id' => 1,
                    'status_batal' => 0,
                ]
            );
        }

        $masterIds = DB::table('tindakan')
            ->whereIn('kode_tindakan', array_keys($this->master))
            ->pluck('tindakan_id', 'kode_tindakan');

        if (Schema::hasTable('administrasi_harga')) {
            $this->salinAdministrasi($masterIds['ADMINISTRASI'] ?? null, $now);
        }
        if (Schema::hasTable('kamar_harga')) {
            $this->salinKamar($masterIds['KAMAR'] ?? null, $now);
        }
        if (Schema::hasTable('visite_harga')) {
            $this->salinVisite($masterIds['VISITE'] ?? null, $now);
        }
        if (Schema::hasTable('komponen_biaya')) {
            $this->salinKomponen($masterIds, $now);
        }

        Schema::dropIfExists('komponen_biaya');
        Schema::dropIfExists('visite_harga');
        Schema::dropIfExists('administrasi_harga');
        Schema::dropIfExists('kamar_harga');
    }

    public function down(): void
    {
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

        $kategoriIds = DB::table('kategori_tindakan')
            ->whereIn('nama_kategori_tindakan', array_values($this->kategori))
            ->pluck('kategori_tindakan_id', 'nama_kategori_tindakan');
        $masterIds = DB::table('tindakan')
            ->whereIn('kode_tindakan', array_keys($this->master))
            ->pluck('tindakan_id', 'kode_tindakan');

        $this->kembalikanAdministrasi($masterIds['ADMINISTRASI'] ?? null);
        $this->kembalikanKamar($masterIds['KAMAR'] ?? null);
        $this->kembalikanVisite($masterIds['VISITE'] ?? null);
        $this->kembalikanKomponen($masterIds, $kategoriIds);

        DB::table('tindakan_harga')
            ->whereIn('tindakan_id', $masterIds->values()->all())
            ->delete();

        foreach (['jenis_kamar', 'jenis_rawat', 'jabatan_id', 'profesi_id', 'bagian_id', 'nasabah_id'] as $kolom) {
            if (Schema::hasColumn('tindakan_harga', $kolom)) {
                Schema::table('tindakan_harga', function (Blueprint $table) use ($kolom) {
                    $table->dropColumn($kolom);
                });
            }
        }

        $duplikat = DB::table('tindakan_harga')
            ->select('tindakan_id', 'kelas_ruang_id')
            ->groupBy('tindakan_id', 'kelas_ruang_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (! $duplikat) {
            Schema::table('tindakan_harga', function (Blueprint $table) {
                $table->unique(['tindakan_id', 'kelas_ruang_id']);
            });
        }
    }

    private function salinAdministrasi(?int $tindakanId, $now): void
    {
        if (! $tindakanId) {
            return;
        }

        foreach (DB::table('administrasi_harga')->get() as $row) {
            DB::table('tindakan_harga')->insert([
                'tindakan_id' => $tindakanId,
                'kelas_ruang_id' => $row->kelas_ruang_id,
                'nasabah_id' => $row->nasabah_id,
                'jenis_rawat' => $row->jenis_rawat,
                'tarif' => $row->tarif,
                'tanggal_berlaku' => $row->tanggal_berlaku,
                'tanggal_akhir' => $row->tanggal_akhir,
                'keterangan' => $row->keterangan,
                'input_time' => $row->input_time ?? $now,
                'input_user_id' => $row->input_user_id ?? 1,
                'mod_time' => $row->mod_time,
                'mod_user_id' => $row->mod_user_id,
                'status_batal' => $row->status_batal ?? 0,
            ]);
        }
    }

    private function salinKamar(?int $tindakanId, $now): void
    {
        if (! $tindakanId) {
            return;
        }

        foreach (DB::table('kamar_harga')->get() as $row) {
            DB::table('tindakan_harga')->insert([
                'tindakan_id' => $tindakanId,
                'kelas_ruang_id' => $row->kelas_ruang_id,
                'nasabah_id' => $row->nasabah_id,
                'bagian_id' => $row->bagian_id,
                'jenis_kamar' => $row->jenis_kamar,
                'tarif' => $row->tarif,
                'tanggal_berlaku' => $row->tanggal_berlaku,
                'tanggal_akhir' => $row->tanggal_akhir,
                'keterangan' => $row->keterangan,
                'input_time' => $row->input_time ?? $now,
                'input_user_id' => $row->input_user_id ?? 1,
                'mod_time' => $row->mod_time,
                'mod_user_id' => $row->mod_user_id,
                'status_batal' => $row->status_batal ?? 0,
            ]);
        }
    }

    private function salinVisite(?int $tindakanId, $now): void
    {
        if (! $tindakanId) {
            return;
        }

        foreach (DB::table('visite_harga')->get() as $row) {
            DB::table('tindakan_harga')->insert([
                'tindakan_id' => $tindakanId,
                'kelas_ruang_id' => $row->kelas_ruang_id,
                'nasabah_id' => $row->nasabah_id,
                'profesi_id' => $row->profesi_id,
                'jabatan_id' => $row->jabatan_id,
                'jenis_rawat' => $row->jenis_rawat,
                'tarif' => $row->tarif,
                'tanggal_berlaku' => $row->tanggal_berlaku,
                'tanggal_akhir' => $row->tanggal_akhir,
                'keterangan' => $row->keterangan,
                'input_time' => $row->input_time ?? $now,
                'input_user_id' => $row->input_user_id ?? 1,
                'mod_time' => $row->mod_time,
                'mod_user_id' => $row->mod_user_id,
                'status_batal' => $row->status_batal ?? 0,
            ]);
        }
    }

    private function salinKomponen($masterIds, $now): void
    {
        foreach (DB::table('komponen_biaya')->get() as $row) {
            $tindakanId = $masterIds[$row->kode] ?? null;
            if (! $tindakanId) {
                continue;
            }

            $sudahAda = DB::table('tindakan_harga')
                ->where('tindakan_id', $tindakanId)
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->exists();

            if ($sudahAda || (float) $row->harga_default <= 0) {
                continue;
            }

            DB::table('tindakan_harga')->insert([
                'tindakan_id' => $tindakanId,
                'tarif' => $row->harga_default,
                'input_time' => $row->input_time ?? $now,
                'input_user_id' => $row->input_user_id ?? 1,
                'status_batal' => 0,
            ]);
        }
    }

    private function kembalikanAdministrasi(?int $tindakanId): void
    {
        if (! $tindakanId) {
            return;
        }

        foreach (DB::table('tindakan_harga')->where('tindakan_id', $tindakanId)->get() as $row) {
            DB::table('administrasi_harga')->insert([
                'jenis_rawat' => $row->jenis_rawat ?: 'RJ',
                'kelas_ruang_id' => $row->kelas_ruang_id,
                'nasabah_id' => $row->nasabah_id,
                'tarif' => $row->tarif,
                'tanggal_berlaku' => $row->tanggal_berlaku,
                'tanggal_akhir' => $row->tanggal_akhir,
                'keterangan' => $row->keterangan,
                'input_time' => $row->input_time,
                'input_user_id' => $row->input_user_id,
                'mod_time' => $row->mod_time,
                'mod_user_id' => $row->mod_user_id,
                'status_batal' => $row->status_batal,
            ]);
        }
    }

    private function kembalikanKamar(?int $tindakanId): void
    {
        if (! $tindakanId) {
            return;
        }

        foreach (DB::table('tindakan_harga')->where('tindakan_id', $tindakanId)->get() as $row) {
            DB::table('kamar_harga')->insert([
                'bagian_id' => $row->bagian_id,
                'kelas_ruang_id' => $row->kelas_ruang_id,
                'nasabah_id' => $row->nasabah_id,
                'jenis_kamar' => $row->jenis_kamar,
                'tarif' => $row->tarif,
                'tanggal_berlaku' => $row->tanggal_berlaku,
                'tanggal_akhir' => $row->tanggal_akhir,
                'keterangan' => $row->keterangan,
                'input_time' => $row->input_time,
                'input_user_id' => $row->input_user_id,
                'mod_time' => $row->mod_time,
                'mod_user_id' => $row->mod_user_id,
                'status_batal' => $row->status_batal,
            ]);
        }
    }

    private function kembalikanVisite(?int $tindakanId): void
    {
        if (! $tindakanId) {
            return;
        }

        foreach (DB::table('tindakan_harga')->where('tindakan_id', $tindakanId)->get() as $row) {
            DB::table('visite_harga')->insert([
                'profesi_id' => $row->profesi_id,
                'jabatan_id' => $row->jabatan_id,
                'kelas_ruang_id' => $row->kelas_ruang_id,
                'nasabah_id' => $row->nasabah_id,
                'jenis_rawat' => $row->jenis_rawat,
                'tarif' => $row->tarif,
                'tanggal_berlaku' => $row->tanggal_berlaku,
                'tanggal_akhir' => $row->tanggal_akhir,
                'keterangan' => $row->keterangan,
                'input_time' => $row->input_time,
                'input_user_id' => $row->input_user_id,
                'mod_time' => $row->mod_time,
                'mod_user_id' => $row->mod_user_id,
                'status_batal' => $row->status_batal,
            ]);
        }
    }

    private function kembalikanKomponen($masterIds, $kategoriIds): void
    {
        foreach ($this->master as $kode => [$nama, $kategori]) {
            $tindakanId = $masterIds[$kode] ?? null;
            if (! $tindakanId) {
                continue;
            }

            $harga = DB::table('tindakan_harga')
                ->where('tindakan_id', $tindakanId)
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->orderByDesc('tindakan_harga_id')
                ->value('tarif') ?? 0;

            DB::table('komponen_biaya')->updateOrInsert(
                ['kode' => $kode],
                [
                    'nama' => $nama,
                    'kategori' => $kategori,
                    'harga_default' => $harga,
                    'is_aktif' => 1,
                    'input_time' => now(),
                    'input_user_id' => 1,
                    'status_batal' => 0,
                ]
            );
        }
    }
};
