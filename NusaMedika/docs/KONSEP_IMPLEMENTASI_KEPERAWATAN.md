# MASTER IMPLEMENTASI + FORM IMPLEMENTASI KEPERAWATAN (EMR)

> **Pembaruan:** tabel `bill_temp` dan model `BillTemp` sudah dihapus. Billing disatukan di tabel `billing` (penanda selesai = `billing.status_selesai`). Referensi `bill_temp` di bawah adalah konteks historis.

> Status: **Sudah diimplementasikan** — DB sudah di-migrate dan di-seed.
> Dua bagian: (1) master katalog di Administrator, (2) form pencatatan di Dashboard Pasien.

---

## 1. Keputusan yang dikunci

| Aspek | Keputusan |
|---|---|
| Isi Master Implementasi | `kode_implementasi` + `nama_implementasi` saja |
| Lokasi master | Administrator → Manajemen Master (`menu_id = 6`), sub_menu 62 |
| Letak form | **Form EMR baru** + sub-menu baru di dashboard EMR, di bawah **Catatan Keperawatan** (`dashboard_menu` 2) |
| Baris per pengisian | **Satu baris** (satu implementasi per form) |
| Respon | **Free text** (textarea) |
| Tanggal & jam | **Wajib diisi manual**, tanpa nilai default |
| Data awal | Seed 15 contoh implementasi |

| Lokasi master | Administrator → Manajemen Master (`menu_id = 6`), sub_menu 62 |

---

## 2. Master Implementasi

### 2.1 Migration

`2026_10_01_000007_create_implementasi_table.php` — tabel `implementasi`:

| Kolom | Tipe |
|---|---|
| `implementasi_id` | `increments` (PK) |
| `input_time` / `input_user_id` / `mod_time` / `mod_user_id` | kolom audit |
| `status_batal` | `smallInteger` |
| `kode_implementasi` | `string(20)` unique |
| `nama_implementasi` | `string(255)` |

Sengaja tanpa kategori/uraian — mengikuti pola master sederhana (`barang_jenis`).

### 2.2 Model

`App\Models\Implementasi` — `$primaryKey = 'implementasi_id'`, `$fillable` dua kolom, scope `aktif()`.

### 2.3 Controller & view

`Administrator\ManajemenMaster\MasterImplementasi\MasterImplementasiController` (sub_menu 62, `file_sub_menu = 'Administrator/ManajemenMaster/MasterImplementasi/master_implementasi'` → URI `/master_implementasi`, route `admin.master_implementasi.*`).

- `index` — filter `search` di kode + nama, paginate 10.
- `store`/`update` — kode dinormalisasi jadi huruf besar (`i-099` → `I-099`).
- `destroy` — soft delete.
- `validated()` — `Rule::unique('implementasi','kode_implementasi')->ignore($id, 'implementasi_id')`. **Nama kolom PK wajib eksplisit**, karena default Laravel adalah `id` yang tidak ada di tabel ini.

Views: `master_implementasi.blade.php` (index) + `_form.blade.php` (dipakai create & edit).

### 2.4 Baseline (15 contoh)

`ImplementasiSeeder` — `I-001` Perawatan Luka (Irigasi dan Balans) · `I-002` Perawatan Kateter Urin · `I-003` Mobilisasi dan Latihan Jalan · `I-004` Latihan Pernapasan Diafragma · `I-005` Pencegahan Risiko Jatuh · `I-006` Perubahan Posisi Tubuh · `I-007` Intake Cairan Oral · `I-008` Edukasi Nutrisi · `I-009` Latihan Buang Air Besar · `I-010` Pencegahan Dekubitus · `I-011` Latihan Menelan · `I-012` Perawatan Mulut dan Oral Hygiene · `I-013` Pemantauan Tanda Vital · `I-014` Dukungan Emosional · `I-015` Persiapan Pulang Pasien.

Idempotent (`updateOrInsert` keyed on `kode_implementasi`). Terdaftar di `DatabaseSeeder` setelah `JadwalDokterSeeder`, dan `'implementasi'` masuk daftar `resetSequences()`.

---

## 3. Form Implementasi Keperawatan (EMR)

### 3.1 Form & dashboard

| Tabel | Nilai |
|---|---|
| `form` | `form_id = 9`, `nama_form` "Implementasi Keperawatan", `slug = implementasi_keperawatan`, `id_dash_menu = '2.8'`, flag `ri/rj/igd/mcu = 1` |
| `dashboard_menu_sub` | id **8**, `dashboard_menu_id = 2` (Catatan Keperawatan), `nama_sub_menu = 'Implementasi Keperawatan'`, **tanpa extra** |

**Kenapa sub-menu tanpa extra:** view `header_ehr` memakai `CONCAT_WS('.', menu, sub, extra)`. Karena `CONCAT_WS` melewati argumen NULL, `id_dash_menu` menjadi `"2.8"` (bukan `"2.8."`). `EmrDashboardController` lalu menyimpan `['id' => ...]` pada entri sub tersebut, dan `EmrDashboard.blade.php:46` merendernya sebagai **link langsung** dengan `Str::slug($subMenuName, '_')`.

> **Gotcha wajib:** `Str::slug('Implementasi Keperawatan', '_')` = `implementasi_keperawatan` **harus sama persis** dengan `form.slug`. Kalau tidak, form tidak muncul di dashboard dan tampil "Unsupported".
>
> Gotcha lain: **`nama_menu` tidak pernah jadi URL** — hanya label. Jadi menuteratas tanpa sub sama sekali **tidak bisa** diklik. Form EMR wajib punya minimal satu sub-menu.

### 3.2 Objek & mapping

Objek baru 121–126:

| variabel | objek | kolom form | aturan |
|---|---|---|---|
| `implementasi_id` | 121 Implementasi | select2 dari master | **wajib** |
| `nama_implementasi` | 122 Nama Implementasi | readonly preview | diisi server |
| `tanggal_implementasi` | 123 Tanggal Implementasi | `<input type="date">` | **wajib** |
| `waktu_implementasi` | 124 Waktu Implementasi | `<input type="time">` | **wajib** (`H:i`) |
| `keterangan_implementasi` | 125 Keterangan Implementasi | textarea | opsional (max 1000) |
| `respon_implementasi` | 126 Respon Implementasi | textarea | opsional (max 1000) |

`nama_implementasi` adalah **snapshot**: `filteredData()` mengisinya ulang dari master berdasarkan `implementasi_id`, mengabaikan nilai dari browser. Pola sama dengan `order_laboratorium_detail.nama_tindakan`, tujuannya agar riwayat tetap terbaca bila master di-rename atau di-soft-delete. Diverifikasi: POST `nama_implementasi=NAMA PALSU DARI BROWSER` tetap tersimpan sebagai nama master yang sebenarnya.

> Gotcha: `emr_detail.variabel` hanya `varchar(250)` — nama variabel jangan terlalu panjang. `value` sendiri `TEXT`, jadi keterangan & respon bebas.

### 3.3 Akses EHR

Baris `akses_ehr` untuk form 9: **Dokter (profesi 1)** dan **Perawat (profesi 2)** full CRUD.

> **Gotcha:** daftar akses di `EmrMasterSeeder` ditulis **hardcoded per form_id**, dan `EmrDashboardController` INNER JOIN `akses_ehr`. Tanpa baris untuk form 9, form **tidak muncul di dashboard sama sekali** dan `AksesEhr::can()` selalu `false` (403). Profes lain otomatis kehilangan akses — itulas disengaja.

### 3.4 Controller

`EMR\ImplementasiKeperawatan\ImplementasiKeperawatanController` — terdeteksi otomatis `DynamicFormController` karena `Str::studly('implementasi_keperawatan')` = `ImplementasiKeperawatan`.

- `index` — `abort_unless(AksesEhr::can(..., 'read'), 403)`; mengirim `$implementasiList` (master aktif) untuk dropdown; `$historyGrouped` diambil dari `EmrHelper::historyKunjunganGrouped($registrasi_detail)` — **riwayat kunjungan**, bukan riwayat emr (lihat §6).
- `store`/`update` — memanggil `validated()` (wajib, tanggal/jam), lalu `filteredData()` yang menyaring field terpetakan dan mengisi snapshot nama.
- `destroy` — `EmrHelper::delete()` (soft delete `emr` + `emr_detail`).

View `moduls/EMR/ImplementasiKeperawatan/index.blade.php` memakai `x-emr-split-layout` + `x-emr-history-table`, dengan preview nama implementasi yang tersinkron dari `data-nama` option.

---

## 4. Verifikasi yang sudah dijalankan

| Uji | Hasil |
|---|---|
| Migration 000007 | OK |
| `ImplementasiSeeder` | 15 baris, idempotent |
| 6 route `admin.master_implementasi.*` | terdaftar |
| Halaman master (index/create/edit) | 200 |
| Simpan master `i-099` | tersimpan `I-099` |
| Kode duplikat | ditolak: *"kode implementasi sudah digunakan."* |
| Ubah & hapus master | 302, `status_batal = 1` |
| Sub-menu dashboard | muncul di bawah **Catatan Keperawatan**, URL `implementasi_keperawatan` |
| Form implementasi | 200, 15 opsi master, tanggal tanpa default |
| POST tanpa tanggal/jam | ditolak: *"Kolom tanggal implementasi wajib diisi."* / *"Kolom waktu implementasi wajib diisi."* |
| POST lengkap | 302, 6 baris `emr_detail` dengan `objek_id` benar |
| Edit prefill | select, tanggal, jam, keterangan, respon terisi |
| Snapshot dipalsukan dari browser | ditimpa nilai master |
| Perawat (profesi 2) | 200 |
| Radiografer (profesi 5) | 403 + form tidak muncul di dashboard |
| Hapus baris implementasi | `emr.status_batal = 1`, 0 detail aktif |

---

## 5. Batasan saat ini

- **Satu implementasi per pengisian form.** Lima intervensi dalam satu shift berarti lima entri EMR terpisah (masing-masing punya `emr_id` sendiri dan tampil terpisah di riwayat). Bila implementasi dikumpulkan dalam satu baris per waktu (pivot `implementasi` → `emr`), atau field JSON — perlu redesign.
- Belum ada rekap total per hari / grafik tren.
- Tidak ada integrasi ke `bill_temp` (implementasi tidak dikenakan biaya).
---

## 6. Riwayat: kunjungan, bukan EMR

Dropdown "History" di panel kiri setiap form EMR **bukan** daftar entri EMR. Kontrak
bentuk datanya (dipakai `x-emr-split-layout`):

```php
[ 'Y-m-d' => [ 'Nama Bagian' => registrasi_detail_id, ... ], ... ]
```

Judul dropdown = `Carbon::parse($date)`, dan tiap chip unit menghasilkan tautan
`$getLink($registrasi_detail_id)` ke kunjungan tersebut. Sumbernya adalah
`registrasi_detail` milik seluruh kunjungan pasien yang masih aktif — bukan
`emr`.

Riwayat entri EMR per form sudah diambil sendiri oleh komponen
`x-emr-history-table` (memakai `EmrHelper::getHistoryForForm($slug, $registrasiDetailId)`),
sehingga controller **tidak perlu** menyusunnya.

Query yang sama sebelumnya ditulis inline di 3 controller; sekarang dipusatkan
menjadi `EmrHelper::historyKunjunganGrouped($registrasi_detail)`
(`app/Helpers/EmrHelper.php`), dipakai oleh Pengkajian Awal, Pengkajian Harian,
dan Implementasi Keperawatan.

> Versi pertama controller Implementasi justru menyusun `$historyGrouped` dari
> baris `emr` (label "nama implementasi - jam" -> `emr_id`), sehingga chip unit
> berisi nama implementasi, tautannya mengarah ke `emr_id`, dan daftar kunjungan
> hilang. Gejalanya: dropdown History tidak bisa berpindah antar kunjungan.
