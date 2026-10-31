<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModulMenuSubMenuSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ======== Seeder Module =========
        $moduls = [
            ['modul_id' => 1, 'nama_modul' => 'Registrasi', 'icon_modul' => 'fa-solid fa-clipboard-list', 'urutan_modul' => 1],
            ['modul_id' => 7, 'nama_modul' => 'Farmasi', 'icon_modul' => 'fa-solid fa-prescription-bottle-medical', 'urutan_modul' => 2],
            ['modul_id' => 2, 'nama_modul' => 'Rawat Jalan', 'icon_modul' => 'fa-solid fa-user-injured', 'urutan_modul' => 3],
            ['modul_id' => 9, 'nama_modul' => 'Medical Checkup', 'icon_modul' => 'fa-solid fa-heart-pulse', 'urutan_modul' => 4],
            ['modul_id' => 3, 'nama_modul' => 'Rawat Inap', 'icon_modul' => 'fa-solid fa-bed', 'urutan_modul' => 5],
            ['modul_id' => 4, 'nama_modul' => 'Gawat Darurat', 'icon_modul' => 'fa-solid fa-truck-medical', 'urutan_modul' => 6],
            ['modul_id' => 5, 'nama_modul' => 'Administrator', 'icon_modul' => 'fa-solid fa-gear', 'urutan_modul' => 7],
            ['modul_id' => 6, 'nama_modul' => 'Inventory', 'icon_modul' => 'fa-solid fa-box-archive', 'urutan_modul' => 8],
            ['modul_id' => 8, 'nama_modul' => 'Penunjang Medis', 'icon_modul' => 'fa-solid fa-microscope', 'urutan_modul' => 9],
            ['modul_id' => 10, 'nama_modul' => 'Social', 'icon_modul' => 'fa-solid fa-users', 'urutan_modul' => 10],
            ['modul_id' => 11, 'nama_modul' => 'Billing', 'icon_modul' => 'fa-solid fa-file-invoice-dollar', 'urutan_modul' => 11],
        ];

        foreach ($moduls as $modul) {
            DB::table('modul')->updateOrInsert(
                ['modul_id' => $modul['modul_id']],
                array_merge($modul, [
                    'input_time' => $now,
                    'input_user_id' => 1,
                    'status_batal' => 0,
                ])
            );
        }

        // ========= Seeder Menu ==========
        $menus = [
            // Modul Registrasi (1)
            ['menu_id' => 1, 'modul_id' => 1, 'nama_menu' => 'Pasien', 'urutan_menu' => 1],
            ['menu_id' => 2, 'modul_id' => 1, 'nama_menu' => 'Pendaftaran', 'urutan_menu' => 2],
            // Modul Rawat Jalan (2)
            ['menu_id' => 3, 'modul_id' => 2, 'nama_menu' => 'Pasien', 'urutan_menu' => 1],
            // Modul Rawat Inap (3)
            ['menu_id' => 4, 'modul_id' => 3, 'nama_menu' => 'Pasien', 'urutan_menu' => 1],
            // Modul Gawat Darurat (4)
            ['menu_id' => 5, 'modul_id' => 4, 'nama_menu' => 'Pasien', 'urutan_menu' => 1],
            // Modul Administrator (5)
            ['menu_id' => 6, 'modul_id' => 5, 'nama_menu' => 'Manajemen Master', 'urutan_menu' => 1],
            ['menu_id' => 7, 'modul_id' => 5, 'nama_menu' => 'Manajemen User', 'urutan_menu' => 2],
            ['menu_id' => 8, 'modul_id' => 5, 'nama_menu' => 'Manajemen EMR', 'urutan_menu' => 3],
            ['menu_id' => 18, 'modul_id' => 5, 'nama_menu' => 'Manajemen Admin', 'urutan_menu' => 4],
            // Modul Inventory (6)
            ['menu_id' => 9, 'modul_id' => 6, 'nama_menu' => 'Pesanan', 'urutan_menu' => 1],
            ['menu_id' => 10, 'modul_id' => 6, 'nama_menu' => 'Penerimaan', 'urutan_menu' => 2],
            ['menu_id' => 11, 'modul_id' => 6, 'nama_menu' => 'Stock', 'urutan_menu' => 3],
            ['menu_id' => 12, 'modul_id' => 6, 'nama_menu' => 'Distribusi', 'urutan_menu' => 4],
            // Modul Farmasi (7)
            ['menu_id' => 13, 'modul_id' => 7, 'nama_menu' => 'Resep', 'urutan_menu' => 1],
            // Modul Penunjang Medis (8)
            ['menu_id' => 14, 'modul_id' => 8, 'nama_menu' => 'Laboratorium', 'urutan_menu' => 1],
            ['menu_id' => 15, 'modul_id' => 8, 'nama_menu' => 'Radiologi', 'urutan_menu' => 2],
            ['menu_id' => 16, 'modul_id' => 8, 'nama_menu' => 'Rehabilitasi Medik', 'urutan_menu' => 3],
            // Modul Medical Checkup (9)
            ['menu_id' => 17, 'modul_id' => 9, 'nama_menu' => 'Pasien', 'urutan_menu' => 1],
            // Modul Social (10)
            ['menu_id' => 19, 'modul_id' => 10, 'nama_menu' => 'Interaksi', 'urutan_menu' => 1],
            // Modul Billing (11)
            ['menu_id' => 20, 'modul_id' => 11, 'nama_menu' => 'Transaksi', 'urutan_menu' => 1],
        ];

        foreach ($menus as $menu) {
            DB::table('menu')->updateOrInsert(
                ['menu_id' => $menu['menu_id']],
                array_merge($menu, [
                    'input_time' => $now,
                    'input_user_id' => 1,
                    'status_batal' => 0,
                ])
            );
        }

        // ========= Seeder Sub Menu ==========
        // file_sub_menu = path view lengkap BESERTA nama file blade (tanpa .blade.php).
        // Segmen terakhir (basename) = URI & nama route; folder = namespace controller & path view.
        $subMenus = [
            // Menu Pasien (1) - Registrasi
            ['sub_menu_id' => 1, 'menu_id' => 1, 'nama_sub_menu' => 'Daftar Pasien', 'file_sub_menu' => 'Registrasi/Pasien/DaftarPasien/daftar_pasien', 'urutan_sub_menu' => 1],
            ['sub_menu_id' => 2, 'menu_id' => 1, 'nama_sub_menu' => 'Nasabah Pasien', 'file_sub_menu' => 'Registrasi/Pasien/NasabahPasien/nasabah_pasien', 'urutan_sub_menu' => 2],
            // Menu Pendaftaran (2) - Registrasi
            ['sub_menu_id' => 3, 'menu_id' => 2, 'nama_sub_menu' => 'List Pelayanan Pasien', 'file_sub_menu' => 'Registrasi/Pendaftaran/ListPelayananPasien/list_pelayanan_pasien', 'urutan_sub_menu' => 1],
            ['sub_menu_id' => 4, 'menu_id' => 2, 'nama_sub_menu' => 'Daftar Rawat Jalan', 'file_sub_menu' => 'Registrasi/Pendaftaran/DaftarRajal/daftar_rajal', 'urutan_sub_menu' => 2],
            ['sub_menu_id' => 5, 'menu_id' => 2, 'nama_sub_menu' => 'Daftar Rawat Inap', 'file_sub_menu' => 'Registrasi/Pendaftaran/DaftarRanap/daftar_ranap', 'urutan_sub_menu' => 3],
            ['sub_menu_id' => 6, 'menu_id' => 2, 'nama_sub_menu' => 'Registrasi IGD', 'file_sub_menu' => 'Registrasi/Pendaftaran/DaftarGawatDarurat/daftar_gawat_darurat', 'urutan_sub_menu' => 4],
            ['sub_menu_id' => 7, 'menu_id' => 2, 'nama_sub_menu' => 'Registrasi IGD Obgyn', 'file_sub_menu' => 'Registrasi/Pendaftaran/DaftarGawatDaruratObstetriGinekologi/daftar_gawat_darurat_obstetri_ginekologi', 'urutan_sub_menu' => 5],
            ['sub_menu_id' => 48, 'menu_id' => 2, 'nama_sub_menu' => 'Daftar Medical Checkup', 'file_sub_menu' => 'Registrasi/Pendaftaran/DaftarMedicalCheckup/daftar_medical_checkup', 'urutan_sub_menu' => 6],
            // Menu Pasien (3) - Rawat Jalan
            ['sub_menu_id' => 12, 'menu_id' => 3, 'nama_sub_menu' => 'List Pasien Dokter', 'file_sub_menu' => 'RawatJalan/Pasien/ListPasienDokter/list_pasien_dokter', 'urutan_sub_menu' => 1],
            ['sub_menu_id' => 29, 'menu_id' => 3, 'nama_sub_menu' => 'List Pasien', 'file_sub_menu' => 'RawatJalan/Pasien/ListPasienRajal/list_pasien_rajal', 'urutan_sub_menu' => 2],
            // Menu Pasien (4) - Rawat Inap
            ['sub_menu_id' => 13, 'menu_id' => 4, 'nama_sub_menu' => 'List Pasien Ranap', 'file_sub_menu' => 'RawatInap/Pasien/ListPasienRanap/list_pasien_ranap', 'urutan_sub_menu' => 1],
            ['sub_menu_id' => 52, 'menu_id' => 4, 'nama_sub_menu' => 'Bed Management', 'file_sub_menu' => 'RawatInap/Pasien/BedManagement/bed_management', 'urutan_sub_menu' => 2],
            // Menu Pasien (5) - Gawat Darurat
            ['sub_menu_id' => 14, 'menu_id' => 5, 'nama_sub_menu' => 'List Pasien IGD', 'file_sub_menu' => 'GawatDarurat/Pasien/ListPasienGawatDarurat/list_pasien_gawat_darurat', 'urutan_sub_menu' => 1],
            // Menu Manajemen Master (6) - Administrator
            ['sub_menu_id' => 15, 'menu_id' => 6, 'nama_sub_menu' => 'Modul', 'file_sub_menu' => 'Administrator/ManajemenMaster/Modul/modul', 'urutan_sub_menu' => 1],
            ['sub_menu_id' => 46, 'menu_id' => 6, 'nama_sub_menu' => 'Master Tindakan', 'file_sub_menu' => 'Administrator/ManajemenMaster/MasterTindakan/master_tindakan', 'urutan_sub_menu' => 1],
            ['sub_menu_id' => 16, 'menu_id' => 6, 'nama_sub_menu' => 'Menu', 'file_sub_menu' => 'Administrator/ManajemenMaster/Menu/menu', 'urutan_sub_menu' => 2],
            ['sub_menu_id' => 17, 'menu_id' => 6, 'nama_sub_menu' => 'Sub Menu', 'file_sub_menu' => 'Administrator/ManajemenMaster/SubMenu/sub_menu', 'urutan_sub_menu' => 3],
            ['sub_menu_id' => 19, 'menu_id' => 6, 'nama_sub_menu' => 'Bagian', 'file_sub_menu' => 'Administrator/ManajemenMaster/Bagian/bagian', 'urutan_sub_menu' => 4],
            ['sub_menu_id' => 20, 'menu_id' => 6, 'nama_sub_menu' => 'Profesi', 'file_sub_menu' => 'Administrator/ManajemenMaster/Profesi/profesi', 'urutan_sub_menu' => 5],
            ['sub_menu_id' => 21, 'menu_id' => 6, 'nama_sub_menu' => 'Jabatan', 'file_sub_menu' => 'Administrator/ManajemenMaster/Jabatan/jabatan', 'urutan_sub_menu' => 6],
            ['sub_menu_id' => 22, 'menu_id' => 6, 'nama_sub_menu' => 'Pegawai', 'file_sub_menu' => 'Administrator/ManajemenMaster/Pegawai/pegawai', 'urutan_sub_menu' => 7],
            ['sub_menu_id' => 23, 'menu_id' => 6, 'nama_sub_menu' => 'Referensi Bagian', 'file_sub_menu' => 'Administrator/ManajemenMaster/ReferensiBagianId/referensi_bagian_id', 'urutan_sub_menu' => 8],
            ['sub_menu_id' => 24, 'menu_id' => 6, 'nama_sub_menu' => 'Wilayah', 'file_sub_menu' => 'Administrator/ManajemenMaster/Wilayah/wilayah', 'urutan_sub_menu' => 9],
            ['sub_menu_id' => 25, 'menu_id' => 6, 'nama_sub_menu' => 'Master Nasabah', 'file_sub_menu' => 'Administrator/ManajemenMaster/Nasabah/nasabah', 'urutan_sub_menu' => 10],
            ['sub_menu_id' => 26, 'menu_id' => 6, 'nama_sub_menu' => 'Master Kelas', 'file_sub_menu' => 'Administrator/ManajemenMaster/Kelas/kelas', 'urutan_sub_menu' => 11],
            ['sub_menu_id' => 27, 'menu_id' => 6, 'nama_sub_menu' => 'Jadwal Dokter', 'file_sub_menu' => 'Administrator/ManajemenMaster/JadwalDokter/jadwal_dokter', 'urutan_sub_menu' => 12],
            ['sub_menu_id' => 28, 'menu_id' => 6, 'nama_sub_menu' => 'ICD', 'file_sub_menu' => 'Administrator/ManajemenMaster/Icd/icd', 'urutan_sub_menu' => 13],
            ['sub_menu_id' => 35, 'menu_id' => 6, 'nama_sub_menu' => 'Barang', 'file_sub_menu' => 'Administrator/ManajemenMaster/Barang/barang', 'urutan_sub_menu' => 14],
            ['sub_menu_id' => 36, 'menu_id' => 6, 'nama_sub_menu' => 'Jenis Barang', 'file_sub_menu' => 'Administrator/ManajemenMaster/JenisBarang/jenis_barang', 'urutan_sub_menu' => 15],
            ['sub_menu_id' => 37, 'menu_id' => 6, 'nama_sub_menu' => 'Supplier', 'file_sub_menu' => 'Administrator/ManajemenMaster/Supplier/supplier', 'urutan_sub_menu' => 16],
            ['sub_menu_id' => 38, 'menu_id' => 6, 'nama_sub_menu' => 'Distributor', 'file_sub_menu' => 'Administrator/ManajemenMaster/Distributor/distributor', 'urutan_sub_menu' => 17],
            ['sub_menu_id' => 50, 'menu_id' => 6, 'nama_sub_menu' => 'Surat Keterangan MCU', 'file_sub_menu' => 'Administrator/ManajemenMaster/SuketMedicalCheckup/suket_medical_checkup', 'urutan_sub_menu' => 18],
            ['sub_menu_id' => 51, 'menu_id' => 6, 'nama_sub_menu' => 'Master Bed', 'file_sub_menu' => 'Administrator/ManajemenMaster/Bed/bed', 'urutan_sub_menu' => 19],
            ['sub_menu_id' => 59, 'menu_id' => 6, 'nama_sub_menu' => 'Master Kategori Tindakan', 'file_sub_menu' => 'Administrator/ManajemenMaster/MasterKategoriTindakan/master_kategori_tindakan', 'urutan_sub_menu' => 20],
            ['sub_menu_id' => 60, 'menu_id' => 6, 'nama_sub_menu' => 'Master Group Tindakan', 'file_sub_menu' => 'Administrator/ManajemenMaster/MasterGroupTindakan/master_group_tindakan', 'urutan_sub_menu' => 21],
            ['sub_menu_id' => 61, 'menu_id' => 6, 'nama_sub_menu' => 'Master Tarif', 'file_sub_menu' => 'Administrator/ManajemenMaster/MasterTarif/master_tarif', 'urutan_sub_menu' => 22],
            ['sub_menu_id' => 62, 'menu_id' => 6, 'nama_sub_menu' => 'Master Implementasi', 'file_sub_menu' => 'Administrator/ManajemenMaster/MasterImplementasi/master_implementasi', 'urutan_sub_menu' => 23],
            ['sub_menu_id' => 63, 'menu_id' => 6, 'nama_sub_menu' => 'Master Diet', 'file_sub_menu' => 'Administrator/ManajemenMaster/MasterDiet/master_diet', 'urutan_sub_menu' => 24],
            ['sub_menu_id' => 64, 'menu_id' => 6, 'nama_sub_menu' => 'Master Diagnosa Diet', 'file_sub_menu' => 'Administrator/ManajemenMaster/MasterDiagnosaDiet/master_diagnosa_diet', 'urutan_sub_menu' => 25],
            // Menu Manajemen User (7) - Administrator
            ['sub_menu_id' => 18, 'menu_id' => 7, 'nama_sub_menu' => 'User', 'file_sub_menu' => 'Administrator/ManajemenUser/User/user', 'urutan_sub_menu' => 1],
            // Menu Manajemen EMR (8) - Administrator
            ['sub_menu_id' => 30, 'menu_id' => 8, 'nama_sub_menu' => 'Dashboard Menu', 'file_sub_menu' => 'Administrator/ManajemenEMR/DashboardMenu/dashboard_menu', 'urutan_sub_menu' => 1],
            ['sub_menu_id' => 31, 'menu_id' => 8, 'nama_sub_menu' => 'Akses EHR', 'file_sub_menu' => 'Administrator/ManajemenEMR/AksesEhr/akses_ehr', 'urutan_sub_menu' => 2],
            ['sub_menu_id' => 32, 'menu_id' => 8, 'nama_sub_menu' => 'Form', 'file_sub_menu' => 'Administrator/ManajemenEMR/Form/form', 'urutan_sub_menu' => 3],
            ['sub_menu_id' => 65, 'menu_id' => 8, 'nama_sub_menu' => 'Katalog Form Blanko', 'file_sub_menu' => 'Administrator/ManajemenEMR/KatalogFormBlanko/katalog_form_blanko', 'urutan_sub_menu' => 4],
            // Menu Pesanan (9) - Inventory
            ['sub_menu_id' => 33, 'menu_id' => 9, 'nama_sub_menu' => 'Buat Pesanan', 'file_sub_menu' => 'Inventory/Pesanan/BuatPesanan/buat_pesanan', 'urutan_sub_menu' => 1],
            ['sub_menu_id' => 34, 'menu_id' => 9, 'nama_sub_menu' => 'Setujui Pesanan', 'file_sub_menu' => 'Inventory/Pesanan/SetujuiPesanan/setujui_pesanan', 'urutan_sub_menu' => 2],
            // Menu Penerimaan (10) - Inventory
            ['sub_menu_id' => 39, 'menu_id' => 10, 'nama_sub_menu' => 'Penerimaan Barang', 'file_sub_menu' => 'Inventory/Penerimaan/PenerimaanBarang/penerimaan_barang', 'urutan_sub_menu' => 1],
            // Menu Stock (11) - Inventory
            ['sub_menu_id' => 40, 'menu_id' => 11, 'nama_sub_menu' => 'Stock Barang', 'file_sub_menu' => 'Inventory/Stock/StockBarang/stock_barang', 'urutan_sub_menu' => 1],
            ['sub_menu_id' => 41, 'menu_id' => 11, 'nama_sub_menu' => 'Kartu Stock', 'file_sub_menu' => 'Inventory/Stock/KartuStock/kartu_stock', 'urutan_sub_menu' => 2],
            // Menu Distribusi (12) - Inventory
            ['sub_menu_id' => 42, 'menu_id' => 12, 'nama_sub_menu' => 'Mutasi Barang', 'file_sub_menu' => 'Inventory/Distribusi/MutasiBarang/mutasi_barang', 'urutan_sub_menu' => 1],
            // Menu Resep (13) - Farmasi
            ['sub_menu_id' => 43, 'menu_id' => 13, 'nama_sub_menu' => 'List Pesanan Resep', 'file_sub_menu' => 'Farmasi/Resep/ListPesananResep/list_pesanan_resep', 'urutan_sub_menu' => 1],
            // Menu Laboratorium (14) - Penunjang Medis
            ['sub_menu_id' => 44, 'menu_id' => 14, 'nama_sub_menu' => 'Daftar Pesanan Laboratorium', 'file_sub_menu' => 'PenunjangMedis/Laboratorium/DaftarPesananLaboratorium/daftar_pesanan_laboratorium', 'urutan_sub_menu' => 1],
            // Menu Radiologi (15) - Penunjang Medis
            ['sub_menu_id' => 45, 'menu_id' => 15, 'nama_sub_menu' => 'Daftar Pesanan Radiologi', 'file_sub_menu' => 'PenunjangMedis/Radiologi/DaftarPesananRadiologi/daftar_pesanan_radiologi', 'urutan_sub_menu' => 1],
            // Menu Rehabilitasi Medik (16) - Penunjang Medis
            ['sub_menu_id' => 47, 'menu_id' => 16, 'nama_sub_menu' => 'Daftar Pasien Rehabilitasi Medik', 'file_sub_menu' => 'PenunjangMedis/RehabilitasiMedik/DaftarPasienRehabilitasiMedik/daftar_pasien_rehabilitasi_medik', 'urutan_sub_menu' => 1],
            // Menu Pasien (17) - Medical Checkup
            ['sub_menu_id' => 49, 'menu_id' => 17, 'nama_sub_menu' => 'List Pasien Medical Checkup', 'file_sub_menu' => 'MedicalCheckup/Pasien/ListPasienMedicalCheckup/list_pasien_medical_checkup', 'urutan_sub_menu' => 1],
            // Menu Manajemen Admin (18) - Administrator
            ['sub_menu_id' => 53, 'menu_id' => 18, 'nama_sub_menu' => 'Notifikasi', 'file_sub_menu' => 'Administrator/ManajemenAdmin/Notifikasi/notifikasi', 'urutan_sub_menu' => 1],
            // Menu Interaksi (19) - Social
            ['sub_menu_id' => 54, 'menu_id' => 19, 'nama_sub_menu' => 'Status', 'file_sub_menu' => 'Social/Status/status', 'urutan_sub_menu' => 1],
            ['sub_menu_id' => 55, 'menu_id' => 19, 'nama_sub_menu' => 'Pesan', 'file_sub_menu' => 'Social/Pesan/pesan', 'urutan_sub_menu' => 2],
            ['sub_menu_id' => 56, 'menu_id' => 19, 'nama_sub_menu' => 'Status User', 'file_sub_menu' => 'Social/StatusUser/status_user', 'urutan_sub_menu' => 3],
            ['sub_menu_id' => 57, 'menu_id' => 19, 'nama_sub_menu' => 'Forum', 'file_sub_menu' => 'Social/Forum/forum', 'urutan_sub_menu' => 4],
            ['sub_menu_id' => 58, 'menu_id' => 19, 'nama_sub_menu' => 'Buat Topik', 'file_sub_menu' => 'Social/BuatTopik/buat_topik', 'urutan_sub_menu' => 5],
            // Menu Transaksi (20) - Billing
            ['sub_menu_id' => 66, 'menu_id' => 20, 'nama_sub_menu' => 'Daftar Billing', 'file_sub_menu' => 'Billing/Transaksi/DaftarBilling/daftar_billing', 'urutan_sub_menu' => 1],
            ['sub_menu_id' => 67, 'menu_id' => 20, 'nama_sub_menu' => 'Billing Pasien', 'file_sub_menu' => 'Billing/Transaksi/BillingPasien/billing_pasien', 'urutan_sub_menu' => 2],
        ];

        foreach ($subMenus as $subMenu) {
            DB::table('sub_menu')->updateOrInsert(
                ['sub_menu_id' => $subMenu['sub_menu_id']],
                array_merge($subMenu, [
                    'input_time' => $now,
                    'input_user_id' => 1,
                    'status_batal' => 0,
                ])
            );
        }
    }
}
