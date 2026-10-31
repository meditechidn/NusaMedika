<?php

namespace Database\Seeders;

use App\Helpers\GenerateHelper;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ReferensiBagianSeeder::class,
            BagianSeeder::class,
            SatuanSeeder::class,
            JenisBarangSeeder::class,
            BarangSeeder::class,
            StokAwalSeeder::class,
            ModulMenuSubMenuSeeder::class,
            MasterPegawaiSeeder::class,
            UserSeeder::class,
            BillingMenuSeeder::class,
            StockOpnameMenuSeeder::class,
            WilayahSeeder::class,
            KelasRuangSeeder::class,
            BedSeeder::class,
            SuketMcuSeeder::class,
            NasabahSeeder::class,
            IcdSeeder::class,
            JadwalDokterSeeder::class,
            ImplementasiSeeder::class,
            MasterDietSeeder::class,
            MasterDietMenuSeeder::class,
            KategoriTindakanSeeder::class,
            TindakanSeeder::class,
            GroupTindakanSeeder::class,
            MasterTarifSeeder::class,
            BillingSeeder::class,
            PasienDummySeeder::class,
            EmrMasterSeeder::class,
        ]);
        // Seeder di atas mengisi ID eksplisit (updateOrInsert), sehingga sequence
        // auto-increment tidak ikut maju. Setel ulang sequence agar insert baru
        // via aplikasi (yang memakai auto-increment) tidak bentrok dengan ID seeder.
        $this->resetSequences();
    }

    private function resetSequences(): void
    {
        $tables = [
            'referensi_bagian', 'bagian', 'modul', 'menu', 'sub_menu',
            'profesi', 'jabatan', 'status_kepegawaian', 'pegawai', 'users',
            'provinsi', 'kabupaten', 'kecamatan', 'kelurahan', 'kelas_ruang', 'nasabah',
            'barang_jenis', 'barang',
            'jadwal_dokter', 'suket_mcu', 'implementasi',
            'master_diet', 'master_diagnosa_diet',
            'dashboard_menu', 'dashboard_menu_sub', 'dashboard_menu_sub_extra', 'form', 'akses_ehr',
            'objek', 'objek_form_control',
            'form_blanko', 'form_blanko_field', 'form_blanko_pengisian',
            'kategori_tindakan', 'tindakan', 'tarif',
            'group_tindakan', 'group_tindakan_tindakan',
            'order_laboratorium', 'order_laboratorium_detail',
            'order_radiologi', 'order_radiologi_detail',
            'order_patologi', 'order_patologi_detail',
            'order_mikrobiologi', 'order_mikrobiologi_detail',
            'order_poct', 'order_poct_detail',
            'notifikasi', 'notifikasi_penerima',
            'percakapan', 'pesan', 'status_sosial',
            'forum_topik', 'forum_balasan',
            'billing', 'billing_detail',
        ];

        foreach ($tables as $table) {
            GenerateHelper::resetSequence($table);
        }
    }
}
