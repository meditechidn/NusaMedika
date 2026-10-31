<?php

use App\Http\Controllers\Administrator\ManajemenEMR\KatalogFormBlanko\KatalogFormBlankoController as AdminKatalogFormBlankoController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Billing\Transaksi\BillingPasien\BillingPasienController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\EMR\DynamicFormController;
use App\Http\Controllers\EMR\EmrDashboard\EmrDashboardController;
use App\Http\Controllers\EMR\KatalogFormBlanko\KatalogFormBlankoController;
use App\Http\Controllers\EMR\Konsultasi\KonsultasiController;
use App\Http\Controllers\EMR\Soap\SoapController;
use App\Http\Controllers\Farmasi\Resep\ListPesananResep\ListPesananResepController;
use App\Http\Controllers\Inventory\Pesanan\BuatPesanan\BuatPesananController;
use App\Http\Controllers\Inventory\Pesanan\SetujuiPesanan\SetujuiPesananController;
use App\Http\Controllers\Inventory\Stock\StockOpname\StockOpnameController;
use App\Http\Controllers\Notifikasi\NotifikasiPenerimaController;
use App\Http\Controllers\PenunjangMedis\Laboratorium\DaftarPesananLaboratorium\DaftarPesananLaboratoriumController;
use App\Http\Controllers\PenunjangMedis\Radiologi\DaftarPesananRadiologi\DaftarPesananRadiologiController;
use App\Http\Controllers\PenunjangMedis\RehabilitasiMedik\DaftarPasienRehabilitasiMedik\DaftarPasienRehabilitasiMedikController;
use App\Http\Controllers\RawatInap\Pasien\BedManagement\BedManagementController;
use App\Http\Controllers\Social\Forum\ForumController;
use App\Http\Controllers\Social\Pesan\PesanController;
use Illuminate\Support\Facades\Route;

// ================ DESKRIPSI ============== #
/*
ROUTE PAGE DAN CRUD TIDAK PERLU DIDAFTARKAN DI SINI, KARENA ROUTE PAGE AKAN OTOMATIS TERDAFTAR SESUAI DENGAN NAMA FOLDERNYA, KECUALI JIKA ADA ROUTE CUSTOM.
BEGITUPUN DENGAN ROUTE EMR, ROUTE AKAN OTOMATIS TERDAFTAR SESUAI DENGAN NAMA FORMNYA.
*/

// ================ ROUTE DEFAULT ============== #
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// ================ ROUTE AUTHENTICATION ============== #
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    // ============ DYNAMIC EMR ROUTE ======== #
    // TIDAK PERLU DIUBAH-UBAH INI PATENN YAA  #
    // ======================================= #
    Route::get('/dashboard_pasien/{registrasi_detail_id}', [EmrDashboardController::class, 'index'])->name('dashboard_pasien.index');
    Route::get('/emr/form/{form_name}/{registrasi_detail_id}/{emr_id?}', [DynamicFormController::class, 'index'])->name('emr.dynamic.index');
    Route::post('/emr/form-store/{form_name}/{registrasi_detail_id}', [DynamicFormController::class, 'store'])->name('emr.form.store');
    Route::put('/emr/form-update/{form_name}/{registrasi_detail_id}/{emr_id}', [DynamicFormController::class, 'update'])->name('emr.form.update');
    Route::delete('/emr/form-delete/{form_name}/{registrasi_detail_id}/{emr_id}', [DynamicFormController::class, 'destroy'])->name('emr.form.destroy');

    // ============ ROUTE CUSTOM ============== #
    // DI SINI KALAU MAU TAMBAH ROUTE CUSTOM    #
    // ======================================== #
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/pasien/{jenis}', [DashboardController::class, 'pasien'])->name('dashboard.pasien');

    // ============ ROUTE PROFILE (modal di navbar) ========== #
    // Aksi non-CRUD: user hanya boleh mengubah profile & akun miliknya sendiri.
    Route::put('/profile/pegawai', [ProfileController::class, 'updatePegawai'])->name('profile.pegawai');
    Route::put('/profile/akun', [ProfileController::class, 'updateAkun'])->name('profile.akun');

    // ============ ROUTE SOAP ================= #
    Route::get('/emr/soap/print/{emr_id}', [SoapController::class, 'print'])->name('emr.soap.print');

    // ============ ROUTE KONSULTASI (EMR/Cetak) ============= #
    // Aksi non-CRUD konsultasi (cetak slip/rencana kontrol) didaftarkan manual.
    Route::get('/emr/konsultasi/print/{emr_id}', [KonsultasiController::class, 'print'])->name('emr.konsultasi.print');

    // ============ ROUTE KATALOG FORM BLANKO (EMR, form 129) ============= #
    // Data disimpan di tabel sendiri (form_blanko*), bukan emr/emr_detail.
    // Rute cetak/print didefinisikan dulu + whereNumber('form_blanko') di
    // rute isi agar literal 'cetak'/'print' tidak tertangkap parameter.
    Route::get('/emr/katalog-form-blanko/cetak/{form_blanko}', [KatalogFormBlankoController::class, 'cetakBlanko'])
        ->whereNumber('form_blanko')->name('emr.katalog_form_blanko.cetak');
    Route::get('/emr/katalog-form-blanko/print/{form_blanko}/{registrasi_detail_id}/{pengisian_id}', [KatalogFormBlankoController::class, 'print'])
        ->whereNumber('form_blanko')->whereNumber('registrasi_detail_id')->whereNumber('pengisian_id')->name('emr.katalog_form_blanko.print');
    Route::get('/emr/katalog-form-blanko/{registrasi_detail_id}', [KatalogFormBlankoController::class, 'index'])
        ->whereNumber('registrasi_detail_id')->name('emr.katalog_form_blanko.index');
    Route::get('/emr/katalog-form-blanko/{form_blanko}/{registrasi_detail_id}/{pengisian_id?}', [KatalogFormBlankoController::class, 'isi'])
        ->whereNumber('form_blanko')->whereNumber('registrasi_detail_id')->whereNumber('pengisian_id')->name('emr.katalog_form_blanko.isi');
    Route::post('/emr/katalog-form-blanko/{form_blanko}/{registrasi_detail_id}', [KatalogFormBlankoController::class, 'store'])
        ->whereNumber('form_blanko')->whereNumber('registrasi_detail_id')->name('emr.katalog_form_blanko.store');
    Route::put('/emr/katalog-form-blanko/{form_blanko}/{registrasi_detail_id}/{pengisian_id}', [KatalogFormBlankoController::class, 'update'])
        ->whereNumber('form_blanko')->whereNumber('registrasi_detail_id')->whereNumber('pengisian_id')->name('emr.katalog_form_blanko.update');
    Route::delete('/emr/katalog-form-blanko/{form_blanko}/{registrasi_detail_id}/{pengisian_id}', [KatalogFormBlankoController::class, 'destroy'])
        ->whereNumber('form_blanko')->whereNumber('registrasi_detail_id')->whereNumber('pengisian_id')->name('emr.katalog_form_blanko.destroy');

    // ============ ROUTE KATALOG FORM BLANKO (Admin publish) ============= #
    // Publish/versi+1 di luar form utama edit (nested form tidak boleh).
    Route::post('/katalog_form_blanko/{katalog_form_blanko}/publish', [AdminKatalogFormBlankoController::class, 'publish'])
        ->whereNumber('katalog_form_blanko')->name('admin.katalog_form_blanko.publish');

    // ============ ROUTE INVENTORY ============= #
    // Aksi non-CRUD pemesanan (setujui/tolak/batal) didaftarkan manual agar reusable.
    Route::post('/pemesanan/{pemesanan}/setujui', [SetujuiPesananController::class, 'setujui'])->name('pemesanan.setujui');
    Route::post('/pemesanan/{pemesanan}/tolak', [SetujuiPesananController::class, 'tolak'])->name('pemesanan.tolak');
    Route::post('/pemesanan/{pemesanan}/batal', [BuatPesananController::class, 'batal'])->name('pemesanan.batal');
    Route::get('/pemesanan/{pemesanan}/cetak', [BuatPesananController::class, 'cetak'])->whereNumber('pemesanan')->name('pemesanan.cetak');

    // Hapus seluruh stok aktif pada satu bagian (aksi massal Stock Opname).
    Route::post('/stock_opname/hapus-semua', [StockOpnameController::class, 'hapusSemua'])->name('stock_opname.hapus_semua');

    // ============ ROUTE FARMASI ============= #
    // Aksi non-CRUD peresepan (pilih depo / dispense / batal) didaftarkan manual.
    Route::post('/list_pesanan_resep/depo', [ListPesananResepController::class, 'pilihDepo'])->name('list_pesanan_resep.depo');
    Route::get('/list_pesanan_resep/{peresepan_obat}', [ListPesananResepController::class, 'detail'])->name('list_pesanan_resep.detail');
    Route::get('/list_pesanan_resep/{peresepan_obat}/cetak-tiket', [ListPesananResepController::class, 'cetakTiket'])->name('list_pesanan_resep.cetak_tiket');
    Route::get('/list_pesanan_resep/{peresepan_obat}/cetak-detail', [ListPesananResepController::class, 'cetakDetail'])->name('list_pesanan_resep.cetak_detail');
    Route::get('/list_pesanan_resep/{peresepan_obat}/dispense', [ListPesananResepController::class, 'dispense'])->name('list_pesanan_resep.dispense');
    Route::post('/list_pesanan_resep/{peresepan_obat}/dispense', [ListPesananResepController::class, 'dispenseStore'])->name('list_pesanan_resep.dispense_store');
    Route::post('/list_pesanan_resep/{peresepan_obat}/batal', [ListPesananResepController::class, 'batal'])->name('list_pesanan_resep.batal');

    // ============ ROUTE PENUNJANG MEDIS ============= #
    // Aksi non-CRUD order laboratorium/radiologi (pilih bagian / terima / simpan hasil / selesai / batal / cetak)
    // didaftarkan manual agar reusable & tidak terikat pola CRUD sub_menu.
    // Laboratorium.
    Route::post('/daftar_pesanan_laboratorium/pilih-bagian', [DaftarPesananLaboratoriumController::class, 'pilihBagian'])->name('daftar_pesanan_laboratorium.pilih_bagian');
    Route::get('/daftar_pesanan_laboratorium/{order_laboratorium}', [DaftarPesananLaboratoriumController::class, 'detail'])->name('daftar_pesanan_laboratorium.detail');
    Route::post('/order_laboratorium/{order_laboratorium}/terima', [DaftarPesananLaboratoriumController::class, 'terima'])->name('order_laboratorium.terima');
    Route::post('/order_laboratorium/{order_laboratorium}/simpan-hasil', [DaftarPesananLaboratoriumController::class, 'simpanHasil'])->name('order_laboratorium.simpan_hasil');
    Route::post('/order_laboratorium/{order_laboratorium}/selesai', [DaftarPesananLaboratoriumController::class, 'selesai'])->name('order_laboratorium.selesai');
    Route::post('/order_laboratorium/{order_laboratorium}/batal', [DaftarPesananLaboratoriumController::class, 'batal'])->name('order_laboratorium.batal');
    Route::get('/order_laboratorium/{order_laboratorium}/cetak', [DaftarPesananLaboratoriumController::class, 'cetak'])->name('order_laboratorium.cetak');
    // Radiologi.
    Route::post('/daftar_pesanan_radiologi/pilih-bagian', [DaftarPesananRadiologiController::class, 'pilihBagian'])->name('daftar_pesanan_radiologi.pilih_bagian');
    Route::get('/daftar_pesanan_radiologi/{order_radiologi}', [DaftarPesananRadiologiController::class, 'detail'])->name('daftar_pesanan_radiologi.detail');
    Route::post('/order_radiologi/{order_radiologi}/terima', [DaftarPesananRadiologiController::class, 'terima'])->name('order_radiologi.terima');
    Route::post('/order_radiologi/{order_radiologi}/simpan-hasil', [DaftarPesananRadiologiController::class, 'simpanHasil'])->name('order_radiologi.simpan_hasil');
    Route::post('/order_radiologi/{order_radiologi}/selesai', [DaftarPesananRadiologiController::class, 'selesai'])->name('order_radiologi.selesai');
    Route::post('/order_radiologi/{order_radiologi}/batal', [DaftarPesananRadiologiController::class, 'batal'])->name('order_radiologi.batal');
    Route::get('/order_radiologi/{order_radiologi}/cetak', [DaftarPesananRadiologiController::class, 'cetak'])->name('order_radiologi.cetak');

    // Rehabilitasi Medik — pilih bagian rehab (session) & detail pasien (bukan CRUD, didaftarkan manual).
    Route::post('/daftar_pasien_rehabilitasi_medik/pilih-bagian', [DaftarPasienRehabilitasiMedikController::class, 'pilihBagian'])->name('daftar_pasien_rehabilitasi_medik.pilih_bagian');
    Route::get('/daftar_pasien_rehabilitasi_medik/detail/{registrasi_detail}', [DaftarPasienRehabilitasiMedikController::class, 'detail'])->name('daftar_pasien_rehabilitasi_medik.detail');

    // ============ ROUTE SOCIAL (pesan, status, forum) ========== #
    // Non-CRUD: membuka obrolan, mengirim pesan, dan membalas topik tidak mengikuti pola CRUD sub_menu.
    // whereNumber WAJIB: route manual ini didaftarkan sebelum route auto, sehingga
    // tanpa constraint ia akan.capture 'pesan/create' atau 'forum/create' dan
    // meneruskannya ke show() sebagai id yang tidak ada (404).
    Route::get('/pesan/{percakapan}', [PesanController::class, 'show'])->whereNumber('percakapan')->name('pesan.show');
    Route::post('/pesan/{percakapan}/kirim', [PesanController::class, 'kirim'])->whereNumber('percakapan')->name('pesan.kirim');
    Route::get('/forum/{topik}', [ForumController::class, 'show'])->whereNumber('topik')->name('forum.show');
    Route::post('/forum/{topik}/balas', [ForumController::class, 'balas'])->whereNumber('topik')->name('forum.balas');

    // ============ ROUTE BILLING ============= #
    // Aksi non-CRUD billing pasien: item, void, diskon, tutup/buka, cetak.
    Route::get('/billing_pasien/harga', [BillingPasienController::class, 'harga'])->name('billing_pasien.harga');
    Route::get('/billing_pasien/cetak-episode', [BillingPasienController::class, 'cetakEpisode'])->name('billing_pasien.cetak_episode');
    Route::get('/billing_pasien/cetak-pasien', [BillingPasienController::class, 'cetakPasien'])->name('billing_pasien.cetak_pasien');
    Route::post('/billing_pasien/tutup-semua', [BillingPasienController::class, 'tutupSemua'])->name('billing_pasien.tutup_semua');
    Route::post('/billing_pasien/buka-semua', [BillingPasienController::class, 'bukaSemua'])->name('billing_pasien.buka_semua');
    Route::post('/billing_pasien/{billing}/item', [BillingPasienController::class, 'store'])->whereNumber('billing')->name('billing_pasien.item');
    Route::post('/billing_pasien/{billing}/item/{detail}/void', [BillingPasienController::class, 'voidItem'])->whereNumber('billing')->whereNumber('detail')->name('billing_pasien.void_item');
    Route::post('/billing_pasien/{billing}/diskon', [BillingPasienController::class, 'diskon'])->whereNumber('billing')->name('billing_pasien.diskon');
    Route::post('/billing_pasien/{billing}/tutup', [BillingPasienController::class, 'tutup'])->whereNumber('billing')->name('billing_pasien.tutup');
    Route::post('/billing_pasien/{billing}/buka', [BillingPasienController::class, 'buka'])->whereNumber('billing')->name('billing_pasien.buka');
    Route::post('/billing_pasien/{billing}/void', [BillingPasienController::class, 'void'])->whereNumber('billing')->name('billing_pasien.void');
    Route::get('/billing_pasien/{billing}/cetak', [BillingPasienController::class, 'cetak'])->whereNumber('billing')->name('billing_pasien.cetak');

    // ============ ROUTE NOTIFIKASI (aksi lonceng navbar) ========== #
    // Non-CRUD & milik user sendiri: menutup notifikasi yang masuk.
    Route::post('/notifikasi/{notifikasi}/tutup', [NotifikasiPenerimaController::class, 'tutup'])->name('notifikasi.tutup');
    Route::post('/notifikasi/tutup-semua', [NotifikasiPenerimaController::class, 'tutupSemua'])->name('notifikasi.tutup_semua');

    // ============ ROUTE BED MANAGEMENT ============= #
    // Aksi non-CRUD Rawat Inap (tempatkan / persiapan pulang / lepas bed) didaftarkan manual.
    Route::post('/bed_management/assign', [BedManagementController::class, 'assign'])->name('bed_management.assign');
    Route::post('/bed_management/ready', [BedManagementController::class, 'ready'])->name('bed_management.ready');
    Route::post('/bed_management/release', [BedManagementController::class, 'release'])->name('bed_management.release');
    Route::post('/bed_management/pulang', [BedManagementController::class, 'pulang'])->name('bed_management.pulang');
    Route::post('/bed_management/move', [BedManagementController::class, 'move'])->name('bed_management.move');
    Route::post('/bed_management/pindah_request', [BedManagementController::class, 'pindahRequest'])->name('bed_management.pindah_request');
    Route::post('/bed_management/pindah_approve', [BedManagementController::class, 'pindahApprove'])->name('bed_management.pindah_approve');
    Route::post('/bed_management/pindah_tolak', [BedManagementController::class, 'pindahTolak'])->name('bed_management.pindah_tolak');
});
