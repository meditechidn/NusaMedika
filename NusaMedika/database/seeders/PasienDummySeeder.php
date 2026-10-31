<?php

namespace Database\Seeders;

use App\Helpers\GenerateHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeder data dummy: 1 pasien lengkap dengan nasabah, lalu Patients yang sama
 * didaftarkan HARI INI pada tiga jenis layanan sekaligus:
 *
 *   1. Rawat Jalan (POLI INTERNA)
 *   2. Rawat Inap  (RUANG PERAWATAN MAWAR, incl. penempatan bed)
 *   3. IGD         (INSTALASI GAWAT DARURAT, triase Kuning)
 *
 * Tujuannya supaya ketiga halaman list (List Pasien Dokter / List Pasien Ranap /
 * List Pasien Gawat Darurat) langsung terisi saat pengembangan, tanpa harus
 * daftar manual.
 *
 * Idempotent: dikunci pada `pasien.ktp` (marker dummy) + tanggal masuk hari ini.
 * Menjalankan seeder ini berulang TIDAK membuat pasien/registrasi ganda:
 * data lamalama di-update, registrasi dummy sebelumnya di-soft-delete dulu.
 *
 * Catatan visibilitas list (join wajib, lihat controller masing-masing):
 *   - RJ  & IGD butuh: registrasi_detail + patient_nasabah + nasabah
 *     (RJ juga `registrasi_urut` + `penanggung_rawat`)
 *   - RI  butuh: `penanggung_rawat` + `bed.pasien_id_1` (tanpa ini tidak muncul)
 *   - semua list difilter `penanggung_rawat.rawat_user_id = user yang login`
 *     → dipakai user "dokter" (user_id 3).
 */
class PasienDummySeeder extends Seeder
{
    /** Marker unik agar seeder idempotent. */
    private const KTP_DUMMY = 'DUMMY0000000001';

    public function run(): void
    {
        $now = now();
        $userId = 1;

        // ===== Lookup master data (per NAMA, bukan id hardcode) =====
        $bagian = fn (string $nama) => DB::table('bagian')
            ->where('nama_bagian', $nama)
            ->where(fn ($q) => $q->where('status_batal', '!=', 1)->orWhereNull('status_batal'))
            ->value('bagian_id');

        $kelas = fn (string $nama) => DB::table('kelas_ruang')
            ->where('nama_kelas_ruang', $nama)
            ->where(fn ($q) => $q->where('status_batal', '!=', 1)->orWhereNull('status_batal'))
            ->first();

        $poliRajal = $bagian('POLI INTERNA');
        $ruangRanap = $bagian('RUANG PERAWATAN MAWAR');
        $igd = $bagian('INSTALASI GAWAT DARURAT');

        $hakKelas = $kelas('Kelas 2');   // hak kelas untuk pasien dummy
        $kelasBpjs = $hakKelas ? $hakKelas->kelas_bpjs : null;
        $hakKelasId = $hakKelas ? $hakKelas->kelas_ruang_id : null;

        // User dokter (profesi_id 1) sebagai penanggung rawat supaya baris muncul di list.
        $dokterUserId = (int) DB::table('users')->where('user_name', 'dokter')->value('user_id');
        $dokterPegawaiId = $dokterUserId
            ? DB::table('users')->where('user_id', $dokterUserId)->value('pegawai_id')
            : null;

        if (! $poliRajal || ! $ruangRanap || ! $igd) {
            $this->command?->warn('Bagian (POLI INTERNA / RUANG PERAWATAN MAWAR / IGD) tidak ditemukan — seeder pasien dummy dilewati.');

            return;
        }

        // ===== 1. Pasien dummy (idempotent via ktp marker) =====
        $existing = DB::table('pasien')->where('ktp', self::KTP_DUMMY)->first();

        $pasienData = [
            'nama_pasien' => 'PASIEN DUMMY UJI',
            'tempat_lahir' => 'Makassar',
            'tgl_lahir' => '1990-05-17 00:00:00',
            'ktp' => self::KTP_DUMMY,
            'sim' => null,
            'paspor' => null,
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'golongan_darah' => 'B',
            'kebangsaan' => 'WNI',
            'suku' => 'Bugis',
            'pendidikan' => 'D3',
            'pekerjaan' => 'Karyawan Swasta',
            'status_perkawinan' => 'Kawin',
            'disabilitas' => 'Tidak Ada',
            'alamat' => 'Jl. Uji Coba No. 1, Makassar',
            'kelurahan_id' => DB::table('kelurahan')->where('nama_kelurahan', 'Gambir')->value('kelurahan_id'),
            'no_hp' => '081234567890',
            'no_hp_keluarga' => '081298765432',
            'email' => 'pasien.dummy@example.test',
            'nama_ibu_kandung' => 'Siti Aminah',
            'nama_ayah_kandung' => 'Muhammad Amin',
            'flag_general_consent' => 1,
            'status_batal' => 0,
        ];

        $pasienData = $this->hanyaKolomExisting('pasien', $pasienData);

        if ($existing) {
            $pasienId = (int) $existing->pasien_id;
            DB::table('pasien')->where('pasien_id', $pasienId)->update(array_merge($pasienData, [
                'mod_time' => $now,
                'mod_user_id' => $userId,
            ]));
        } else {
            $pasienId = (int) DB::table('pasien')->insertGetId(array_merge($pasienData, [
                'no_mr' => GenerateHelper::generateNoMr(),
                'input_time' => $now,
                'input_user_id' => $userId,
            ]), 'pasien_id');
        }

        // ===== 2. Nasabah pasien (BPJS + Umum/Mandiri) =====
        $nasabahBpjs = DB::table('nasabah')->where('nama_nasabah', 'BPJS Kesehatan')->value('nasabah_id');
        $nasabahUmum = DB::table('nasabah')->where('nama_nasabah', 'Umum / Mandiri')->value('nasabah_id');

        $pasienNasabahId = DB::table('pasien_nasabah')
            ->where('pasien_id', $pasienId)
            ->where('nasabah_id', $nasabahBpjs)
            ->value('pasien_nasabah_id');

        $pnData = [
            'pasien_id' => $pasienId,
            'nasabah_id' => $nasabahBpjs,
            'no_peserta' => '0001234567890',
            'hak_kelas_id' => $hakKelasId,
            'nama_jenis_kepesertaan' => 'Pekerja penerima upah',
            'kode_jenis_kepesertaan' => '1',
            'status_batal' => 0,
        ];

        $pnData = $this->hanyaKolomExisting('pasien_nasabah', $pnData);

        if ($pasienNasabahId) {
            DB::table('pasien_nasabah')->where('pasien_nasabah_id', $pasienNasabahId)->update($pnData);
        } else {
            $pasienNasabahId = (int) DB::table('pasien_nasabah')->insertGetId(array_merge($pnData, [
                'input_time' => $now,
                'input_user_id' => $userId,
            ]), 'pasien_nasabah_id');
        }

        // Simpan juga penjamin alternatif Umum/Mandiri (secondary).
        if ($nasabahUmum) {
            $pnUmum = [
                'pasien_id' => $pasienId,
                'nasabah_id' => $nasabahUmum,
                'no_peserta' => null,
                'hak_kelas_id' => $hakKelasId,
                'status_batal' => 0,
            ];

            $existsUmum = DB::table('pasien_nasabah')
                ->where('pasien_id', $pasienId)
                ->where('nasabah_id', $nasabahUmum)
                ->exists();

            if (! $existsUmum) {
                DB::table('pasien_nasabah')->insert(array_merge(
                    $this->hanyaKolomExisting('pasien_nasabah', $pnUmum),
                    ['input_time' => $now, 'input_user_id' => $userId]
                ));
            }
        }

        // ===== 3. TIGA registrasi (RJ, RI, IGD) — semuanya hari ini =====
        $tglMasuk = $now->copy()->setTime(8, 0, 0);

        // Bersihkan registrasi dummy sebelumnya (punya pasien ini, masuk hari ini)
        // supaya menjalankan seeder berulang tidak menumpuk baris.
        $this->bersihkanRegistrasiDummy($pasienId, [$now]);

        // 3a. RAWAT JALAN
        $this->daftarRawatJalan([
            'pasien_id' => $pasienId,
            'pasien_nasabah_id' => $pasienNasabahId,
            'jenis_rawat' => env('JENIS_RAWAT_RJ', 'RJ'),
            'tgl_masuk' => $tglMasuk->copy(),
            'bagian_id' => $poliRajal,
            'kelas_id' => $kelasBpjs,
            'hak_kelas_id' => $hakKelasId,
            'prioritas' => 'Berjalan Sendiri',
            'memo' => 'Keluhan dummy: demam ringan dan nyeri kepala sejak 2 hari.',
            'dokter_pegawai_id' => $dokterPegawaiId,
            'dokter_user_id' => $dokterUserId,
            'now' => $now,
            'user_id' => $userId,
            'input_user_id' => $userId,
        ]);

        // 3b. RAWAT INAP (+ penempatan bed)
        $this->daftarRawatInap([
            'pasien_id' => $pasienId,
            'pasien_nasabah_id' => $pasienNasabahId,
            'jenis_rawat' => env('JENIS_RAWAT_RI', 'RI'),
            'tgl_masuk' => $tglMasuk->copy()->addHours(2),
            'bagian_id' => $ruangRanap,
            'kelas_id' => $kelasBpjs,
            'hak_kelas_id' => $hakKelasId,
            'asal' => 'Poliklinik',
            'prioritas' => 'Poliklinik',
            'memo' => json_encode([
                'indikasi' => 'Observasi medis',
                'nama_pj' => 'Ahmad Dummy',
                'hubungan_pj' => 'Suami',
                'nohp_pj' => '081298765432',
            ]),
            'dokter_user_id' => $dokterUserId,
            'now' => $now,
            'user_id' => $userId,
            'input_user_id' => $userId,
        ]);

        // 3c. IGD
        $this->daftarIgd([
            'pasien_id' => $pasienId,
            'pasien_nasabah_id' => $pasienNasabahId,
            'jenis_rawat' => env('JENIS_RAWAT_IGD', 'IGD'),
            'tgl_masuk' => $tglMasuk->copy()->addHours(4),
            'bagian_id' => $igd,
            'kelas_id' => $kelasBpjs,
            'hak_kelas_id' => $hakKelasId,
            'triase' => 'Kuning',
            'cara_masuk' => 'Berjalan Sendiri',
            'lokasi_rawat' => 'Ruang Observasi',
            'memo' => json_encode([
                'pengantar' => 'Dummy (diserahkan keluarga)',
                'hubungan' => 'Keluarga Inti',
                'nohp' => '081298765432',
            ]),
            'dokter_user_id' => $dokterUserId,
            'now' => $now,
            'user_id' => $userId,
            'input_user_id' => $userId,
        ]);

        // Ringkasan
        $this->command?->info('  Pasien dummy "PASIEN DUMMY UJI" siap. No. MR: '.DB::table('pasien')->where('pasien_id', $pasienId)->value('no_mr'));
        $this->command?->info('  Terdaftar hari ini: Rawat Jalan + Rawat Inap + IGD.');
    }

    /**
     * Buang key yang kolomnya tidak ada di tabel.
     *
     * Database lokal bisa punya drift skema (mis. kolom `golongan_darah` di
     * migration vs `gol_darah` di DB yang dibuat dari versi migration lama),
     * sehingga seeder dummy ini tidak hard-gagal. Data yang benar-benar penting
     * (nama, ktp, no_mr) dijamin ada di kedua skema.
     */
    private function hanyaKolomExisting(string $table, array $data): array
    {
        $kolom = Schema::getColumnListing($table);

        return array_intersect_key($data, array_flip($kolom));
    }

    /**
     * Soft-delete registrasi dummy sebelumnya milik pasien ini yang masuk HARI INI
     * beserta detail, registrasi_urut, penanggung_rawat — dan kosongkan
     * bed yang sempat dipakai. Menjaga seeder tetap idempotent.
     */
    private function bersihkanRegistrasiDummy(int $pasienId, array $tanggal): void
    {
        $registrasiIds = DB::table('registrasi')
            ->where('pasien_id', $pasienId)
            ->whereDate('tgl_masuk', $tanggal[0])
            ->where(fn ($q) => $q->whereNull('status_batal')->orWhere('status_batal', 0))
            ->pluck('registrasi_id');

        if ($registrasiIds->isEmpty()) {
            return;
        }

        $detailIds = DB::table('registrasi_detail')
            ->whereIn('registrasi_id', $registrasiIds)
            ->pluck('registrasi_detail_id');

        $now = now();

        DB::table('bed')
            ->where('pasien_id_1', $pasienId)
            ->update([
                'status_bed' => 0,
                'pasien_id_1' => null,
                'tgl_masuk' => null,
                'mod_time' => $now,
            ]);

        DB::table('registrasi_urut')->whereIn('registrasi_detail_id', $detailIds)
            ->update(['status_batal' => 1, 'mod_time' => $now]);

        DB::table('penanggung_rawat')->whereIn('registrasi_id', $registrasiIds)
            ->update(['status_batal' => 1, 'mod_time' => $now]);

        DB::table('registrasi_detail')->whereIn('registrasi_id', $registrasiIds)
            ->update(['status_batal' => 1, 'mod_time' => $now]);

        DB::table('registrasi')->whereIn('registrasi_id', $registrasiIds)
            ->update(['status_batal' => 1, 'mod_time' => $now]);
    }

    // =========================================================================
    // Helper: registrasi rawat jalan
    // =========================================================================
    private function daftarRawatJalan(array $d): void
    {
        $reg = $this->insertRegistrasi([
            'pasien_id' => $d['pasien_id'],
            'pasien_nasabah_id' => $d['pasien_nasabah_id'],
            'tgl_masuk' => $d['tgl_masuk'],
            'jenis_rawat' => $d['jenis_rawat'],
            'prioritas' => $d['prioritas'],
            'memo' => $d['memo'],
            'flag_online' => 0,
            'status_batal' => 0,
            'now' => $d['now'],
            'user_id' => $d['input_user_id'],
        ]);

        $detailId = $this->insertRegistrasiDetail([
            'registrasi_id' => $reg,
            'bagian_id' => $d['bagian_id'],
            'kelas_id' => $d['kelas_id'],
            'hak_kelas_id' => $d['hak_kelas_id'],
            'terima_dari' => 'DALAM',
            'tgl_daftar' => $d['now'],
            'status_batal' => 0,
            'now' => $d['now'],
            'user_id' => $d['input_user_id'],
        ]);

        // registrasi_urut (join wajib list RJ — urutan antrian)
        if ($d['dokter_pegawai_id']) {
            $urutan = DB::table('registrasi_urut')
                ->where('pegawai_id', $d['dokter_pegawai_id'])
                ->where('bagian_id', $d['bagian_id'])
                ->where(fn ($q) => $q->whereNull('status_batal')->orWhere('status_batal', 0))
                ->whereDate('tgl_urut', $d['tgl_masuk'])
                ->max('urutan');

            DB::table('registrasi_urut')->insert([
                'registrasi_detail_id' => $detailId,
                'pegawai_id' => $d['dokter_pegawai_id'],
                'bagian_id' => $d['bagian_id'],
                'urutan' => ((int) $urutan) + 1,
                'tgl_urut' => $d['tgl_masuk'],
                'status_batal' => 0,
                'input_time' => $d['now'],
                'input_user_id' => $d['input_user_id'],
            ]);
        }

        // penanggung_rawat (filter rawat_user_id = user login)
        $this->insertPenanggungRawat($reg, $d);
    }

    // =========================================================================
    // Helper: registrasi rawat inap (+ isi bed)
    // =========================================================================
    private function daftarRawatInap(array $d): void
    {
        $reg = $this->insertRegistrasi([
            'pasien_id' => $d['pasien_id'],
            'pasien_nasabah_id' => $d['pasien_nasabah_id'],
            'tgl_masuk' => $d['tgl_masuk'],
            'tgl_keluar' => null, // masih dirawat → tampil di List Pasien Ranap
            'jenis_rawat' => $d['jenis_rawat'],
            'prioritas' => $d['prioritas'],
            'memo' => $d['memo'],
            'flag_online' => 0,
            'status_batal' => 0,
            'now' => $d['now'],
            'user_id' => $d['input_user_id'],
        ]);

        $this->insertRegistrasiDetail([
            'registrasi_id' => $reg,
            'bagian_id' => $d['bagian_id'],
            'kelas_id' => $d['kelas_id'],
            'hak_kelas_id' => $d['hak_kelas_id'],
            'terima_dari' => 'DALAM',
            'tgl_daftar' => $d['now'],
            'check_in' => $d['tgl_masuk'],
            'status_batal' => 0,
            'now' => $d['now'],
            'user_id' => $d['input_user_id'],
        ]);

        $this->insertPenanggungRawat($reg, $d);

        // PENEMPATAN BED — WAJIB: List Pasien Ranap INNER JOIN bed.pasien_id_1.
        $bed = DB::table('bed')
            ->where('bagian_id', $d['bagian_id'])
            ->where(fn ($q) => $q->where('status_batal', '!=', 1)->orWhereNull('status_batal'))
            ->where(fn ($q) => $q->whereNull('pasien_id_1')->orWhere('pasien_id_1', 0))
            ->orderBy('bed_id')
            ->first();

        if ($bed) {
            DB::table('bed')->where('bed_id', $bed->bed_id)->update([
                'status_bed' => 1, // 1 = terisi
                'pasien_id_1' => $d['pasien_id'],
                'tgl_masuk' => $d['tgl_masuk'],
                'mod_time' => $d['now'],
                'mod_user_id' => $d['input_user_id'],
            ]);
        } else {
            $this->command?->warn('  [Rawat Inap] Tidak ada bed kosong di ruang tersebut — pasien tetap dibuat tapi tidak muncul di List Pasien Ranap.');
        }
    }

    // =========================================================================
    // Helper: registrasi IGD
    // =========================================================================
    private function daftarIgd(array $d): void
    {
        $reg = $this->insertRegistrasi([
            'pasien_id' => $d['pasien_id'],
            'pasien_nasabah_id' => $d['pasien_nasabah_id'],
            'tgl_masuk' => $d['tgl_masuk'],
            'tgl_keluar' => null,
            'jenis_rawat' => $d['jenis_rawat'],
            'prioritas' => $d['triase'], // IGD: priorities = triase
            'memo' => $d['memo'],
            'flag_online' => 0,
            'status_batal' => 0,
            'now' => $d['now'],
            'user_id' => $d['input_user_id'],
        ]);

        $this->insertRegistrasiDetail([
            'registrasi_id' => $reg,
            'bagian_id' => $d['bagian_id'],
            'kelas_id' => $d['kelas_id'],
            'hak_kelas_id' => $d['hak_kelas_id'],
            'terima_dari' => 'LUAR',
            'tgl_daftar' => $d['now'],
            'triase' => $d['triase'],
            'cara_masuk' => $d['cara_masuk'],
            'lokasi_rawat' => $d['lokasi_rawat'],
            'status_batal' => 0,
            'now' => $d['now'],
            'user_id' => $d['input_user_id'],
        ]);

        $this->insertPenanggungRawat($reg, $d);
    }

    // =========================================================================
    // Helper insert generik
    // =========================================================================
    private function insertRegistrasi(array $d): int
    {
        return (int) DB::table('registrasi')->insertGetId([
            'pasien_id' => $d['pasien_id'],
            'pasien_nasabah_id' => $d['pasien_nasabah_id'],
            'tgl_masuk' => $d['tgl_masuk'],
            'tgl_keluar' => $d['tgl_keluar'] ?? null,
            'jenis_rawat' => $d['jenis_rawat'],
            'prioritas' => $d['prioritas'],
            'memo' => $d['memo'],
            'flag_online' => $d['flag_online'],
            'status_batal' => 0,
            'input_time' => $d['now'],
            'input_user_id' => $d['user_id'],
        ], 'registrasi_id');
    }

    private function insertRegistrasiDetail(array $d): int
    {
        return (int) DB::table('registrasi_detail')->insertGetId([
            'registrasi_id' => $d['registrasi_id'],
            'bagian_id' => $d['bagian_id'],
            'kelas_id' => $d['kelas_id'],
            'hak_kelas_id' => $d['hak_kelas_id'],
            'terima_dari' => $d['terima_dari'],
            'tgl_daftar' => $d['tgl_daftar'],
            'check_in' => $d['check_in'] ?? null,
            'triase' => $d['triase'] ?? null,
            'cara_masuk' => $d['cara_masuk'] ?? null,
            'lokasi_rawat' => $d['lokasi_rawat'] ?? null,
            'status_batal' => 0,
            'input_time' => $d['now'],
            'input_user_id' => $d['user_id'],
        ], 'registrasi_detail_id');
    }

    private function insertPenanggungRawat(int $registrasiId, array $d): void
    {
        DB::table('penanggung_rawat')->insert([
            'registrasi_id' => $registrasiId,
            'rawat_user_id' => $d['dokter_user_id'],
            'kirim_user_id' => $d['input_user_id'],
            'status_batal' => 0,
            'input_time' => $d['now'],
            'input_user_id' => $d['input_user_id'],
        ]);
    }
}
