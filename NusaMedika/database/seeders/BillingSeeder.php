<?php

namespace Database\Seeders;

use App\Models\KelasRuang;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BillingSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $kategori = [
            'Administrasi',
            'Kamar',
            'Makanan',
            'Visite',
            'Konsultasi',
            'Emergency',
            'MCU',
        ];

        foreach ($kategori as $nama) {
            DB::table('kategori_tindakan')->updateOrInsert(
                ['nama_kategori_tindakan' => $nama],
                [
                    'input_time' => $now,
                    'input_user_id' => 1,
                    'status_batal' => 0,
                ]
            );
        }

        $kategoriId = fn (string $nama) => DB::table('kategori_tindakan')
            ->where('nama_kategori_tindakan', $nama)
            ->value('kategori_tindakan_id');

        // Semua biaya non-tindakan tetap dicatat sebagai master `tindakan`,
        // dibedakan lewat kategori. Tarifnya disimpan di `tarif`.
        $master = [
            ['kode' => 'ADMINISTRASI', 'nama' => 'Biaya Administrasi', 'kategori' => 'Administrasi'],
            ['kode' => 'KAMAR', 'nama' => 'Kamar Rawat Inap', 'kategori' => 'Kamar'],
            ['kode' => 'MAKANAN', 'nama' => 'Biaya Makanan', 'kategori' => 'Makanan'],
            ['kode' => 'VISITE', 'nama' => 'Visite Dokter', 'kategori' => 'Visite'],
            ['kode' => 'KONSULTASI', 'nama' => 'Jasa Konsultasi', 'kategori' => 'Konsultasi'],
            ['kode' => 'EMERGENCY', 'nama' => 'Tindakan Emergency', 'kategori' => 'Emergency'],
            ['kode' => 'MCU', 'nama' => 'Medical Checkup', 'kategori' => 'MCU'],
            ['kode' => 'LAINNYA', 'nama' => 'Biaya Lainnya', 'kategori' => 'Lainnya'],
        ];

        foreach ($master as $row) {
            DB::table('tindakan')->updateOrInsert(
                ['kode_tindakan' => $row['kode']],
                [
                    'nama_tindakan' => $row['nama'],
                    'kategori_tindakan_id' => $kategoriId($row['kategori']),
                    'input_time' => $now,
                    'input_user_id' => 1,
                    'status_batal' => 0,
                ]
            );
        }

        $tindakanId = fn (string $kode) => DB::table('tindakan')
            ->where('kode_tindakan', $kode)
            ->value('tindakan_id');

        // Tarif default administrasi & kamar (satu baris, kelas_ruang_id NULL).
        $idAdministrasi = $tindakanId('ADMINISTRASI');
        $this->buatTarifJikaBelumAda($idAdministrasi, null, $now);

        $idKamar = $tindakanId('KAMAR');
        $this->buatTarifJikaBelumAda($idKamar, null, $now);

        // Tarif kamar per kelas perawatan (Kelas 1/2/3/VIP/VVIP).
        foreach (KelasRuang::aktif()->get() as $kelas) {
            $this->buatTarifJikaBelumAda($idKamar, (int) $kelas->kelas_ruang_id, $now);
        }
    }

    private function buatTarifJikaBelumAda(?int $tindakanId, ?int $kelasRuangId, $waktu): void
    {
        if (! $tindakanId) {
            return;
        }

        $sudahAda = DB::table('tarif')
            ->where('tindakan_id', $tindakanId)
            ->when($kelasRuangId !== null, fn ($q) => $q->where('kelas_ruang_id', $kelasRuangId), fn ($q) => $q->whereNull('kelas_ruang_id'))
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->exists();

        if ($sudahAda) {
            return;
        }

        DB::table('tarif')->insert([
            'tindakan_id' => $tindakanId,
            'kelas_ruang_id' => $kelasRuangId,
            'tarif' => 0,
            'input_time' => $waktu,
            'input_user_id' => 1,
            'status_batal' => 0,
        ]);
    }
}
