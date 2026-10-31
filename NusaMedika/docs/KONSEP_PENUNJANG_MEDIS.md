# KONSEP MODUL PENUNJANG MEDIS (Laboratorium & Radiologi)

> **Pembaruan:** tabel `bill_temp` dan model `BillTemp` sudah dihapus. Billing disatukan di tabel `billing` (penanda selesai = `billing.status_selesai`). Referensi `bill_temp` di bawah adalah konteks historis.

> Status: **Rancangan / Konsep** — belum diimplementasikan.
> Dokumen ini menjadi acuan implementasi. Semua nama (tabel, folder, route, slug) bersifat keputusan awal dan harus disepakati sebelum migrasi dibuat.

---

## 1. Ringkasan

Fitur ini menghubungkan **dua area** yang sudah ada di MediTechV2:

1. **Order penunjang dari EMR pasien** — Dokter/Perawat memerintahkan pemeriksaan **Laboratorium** dan **Radiologi** langsung dari **Dashboard Pasien** (tab/`dashboard_menu` baru bernama **"Order"** yang berisi *Order Resep*, *Laboratorium*, dan *Radiologi*).
2. **Pengelolaan order & entri hasil oleh petugas** — di modul baru **"Penunjang Medis"**, lewat sub menu **Daftar Pesanan Laboratorium** dan **Daftar Pesanan Radiologi**. Hasil yang sudah terbit **muncul kembali sebagai riwayat/`hasil` di list EMR pasien** (dibaca ulang oleh Dashboard Pasien).

Serta satu **master baru**:

3. **Master Tindakan** — katalog tindakan penunjang yang di-*mapping* ke `bagian`, lengkap dengan **tarif per kelas perawatan** (`kelas_ruang`). Di desain siap dipakai untuk **bridging VClaim / INACBG** dan **pembagian jasa medis** di tahap lanjut.

Aturan kunci yang diminta:

- **Setiap order Laboratorium dan Radiologi WAJIB membuat `registrasi_detail` baru** (bagian sarana penunjang) di dalam `registrasi` yang sama — sehingga order ikut tercatat sebagai *layanan/pelayanan* pasien, siap untuk billing, antrian sarana, dan audit.
- **Bagian Laboratorium & Radiologi masuk `referensi_bagian` "Penunjang Medis"** (baru).
- **Input hasil tidak dilakukan di EMR**, tetapi tetap di sub menu masing-masing (Daftar Pesanan Laboratorium / Radiologi).

---

## 2. Tujuan & Manfaat

| Tujuan | Manfaat |
|---|---|
| Order penunjang terpusat di EMR | Dokter tidak berpindah aplikasi; riwayat order + hasil ada di satu tempat (rekam medis pasien) |
| Setiap order menciptakan `registrasi_detail` | Konsisten dengan model pelayanan existing (ready utk `BillTemp`/billing, antrian, laporan) |
| Hasil kembali ke EMR pasien | Kontinuitas rekam medis; dokter melihat hasil tanpa keluar dashboard pasien |
| Master Tindakan + tarif per kelas | Dasar billing mandiri sekaligus persiapan BPJS (VClaim/INACBG) & bagi jasa medis |

---

## 3. Struktur Modul & Sidebar

### 3.1 Modul "Penunjang Medis" (baru, `modul_id = 8`)

```
Penunjang Medis  (modul_id 8, icon mis. fa-solid fa-microscope)
├── Laboratorium  (menu_id 14)
│   └── Daftar Pesanan Laboratorium   (sub_menu 44)
│       file_sub_menu = PenunjangMedis/Laboratorium/DaftarPesananLaboratorium/daftar_pesanan_laboratorium
├── Radiologi  (menu_id 15)
│   └── Daftar Pesanan Radiologi      (sub_menu 45)
│       file_sub_menu = PenunjangMedis/Radiologi/DaftarPesananRadiologi/daftar_pesanan_radiologi
└── Master  (menu_id 16)
    └── Master Tindakan               (sub_menu 46)
        file_sub_menu = PenunjangMedis/Master/MasterTindakan/master_tindakan
```

Controller (turunan `make:submenu`, lalu diisi):

| Sub Menu | Controller | Route |
|---|---|---|
| Daftar Pesanan Laboratorium | `App\Http\Controllers\PenunjangMedis\Laboratorium\DaftarPesananLaboratorium\DaftarPesananLaboratoriumController` | `daftar_pesanan_laboratorium.*` |
| Daftar Pesanan Radiologi | `App\Http\Controllers\PenunjangMedis\Radiologi\DaftarPesananRadiologi\DaftarPesananRadiologiController` | `daftar_pesanan_radiologi.*` |
| Master Tindakan | `App\Http\Controllers\PenunjangMedis\Master\MasterTindakan\MasterTindakanController` | `master_tindakan.*` |

View: `resources/views/moduls/PenunjangMedis/{Laboratorium,Radiologi,Master}/...` (path == namespace controller). Leaf folder sudah unik (`DaftarPesananLaboratorium`, `DaftarPesananRadiologi`, `MasterTindakan`) — aman sesuai aturan *Modular structure*.

> **Alternatif penempatan Master Tindakan:** bisa juga dipindah ke `Administrator/ManajemenMaster/Tindakan` (route `admin.tindakan.*`) bila nanti tindakan dipakai lintas modul (jasa medis dokter, tariff umum). Konsep ini menempatkannya di dalam modul Penunjang Medis agar modul self-contained; keputusan final boleh diubah asal leaf `Tindakan`/`MasterTindakan` tetap unik.

### 3.2 Dashboard EMR — Menu "Order" (baru)

`dashboard_menu` baru **id 4 "Order"** berisi tiga sub:

```
Order (dashboard_menu_id 4)
├── Order Resep      (dashboard_menu_sub_id 4)  → form slug order_resep      (id_dash_menu = "4.4")
├── Laboratorium     (dashboard_menu_sub_id 5)  → form slug laboratorium     (id_dash_menu = "4.5")
└── Radiologi        (dashboard_menu_sub_id 6)  → form slug radiologi        (id_dash_menu = "4.6")
```

Aturan penting dari `EmrDashboardController`/`header_ehr`:

- **Nama sub menu = slug form** (dipakai dashboard untuk membentuk `form_name`), jadi nama sub harus persis: **`Order Resep`**, **`Laboratorium`**, **`Radiologi`** (bukan `Peresepan Obat`, bukan `Pemeriksaan Lab`).
- `id_dash_menu` pada tabel `form` harus **sama dengan string `CONCAT_WS('.', ...)` di view `header_ehr`** (id aktual baris dashboard) — lihat section *Integrasi EMR*.
- Menu lama `dashboard_menu` 3 "Resep" + sub 3 "Peresepan Obat" di-*soft-delete* (`status_batal = 1`) setelah form `peresepan_obat` dipindah menjadi `order_resep`, supaya tidak ada menu "Resep" kosong yang tampil di dashboard.

---

## 4. Referensi Bagian & Bagian Penunjang

### 4.1 `referensi_bagian` (tambah 1 baris)

| referensi_bagian_id | nama_referensi_bagian |
|---|---|
| 1 | RAWAT JALAN |
| 2 | RAWAT INAP |
| 3 | IGD |
| 4 | GUDANG |
| 5 | DEPO |
| **6** | **PENUNJANG MEDIS** |

Update di `ReferensiBagianSeeder` (idempotent `updateOrInsert`).

### 4.2 `bagian` (tambah minimal 2 baris, di APPENDER agar id existing tidak berubah)

| bagian_id | nama_bagian | referensi_bagian_id |
|---|---|---|
| 36 | INSTALASI LABORATORIUM | 6 |
| 37 | INSTALASI RADIOLOGI | 6 |
| 38 *(opsional)* | INSTALASI LABORATORIUM PATOLOGI ANATOMI | 6 |

Update `BagianSeeder` (yang sudah menghapus permanen & seed ulang `bagian`). **Penting:** jangan sisipkan di tengah list (id existing berubah & merusak data), selalu append di akhir.

Contoh isi jadwal praktik/pelaksana: petugas lab/rad dihubungkan via `pegawai.bagian_id` ke bagian ini (lihat section Akses/Pegawai).

---

## 5. Master Tindakan & Tarif per Kelas

### 5.1 Model data

Dua tabel: **`tindakan`** (katalog) + **`tindakan_harga`** (tarif per kelas).

```
tindakan
├── tindakan_id        (PK, auto-increment)
├── kode_tindakan      varchar(20)  UNIQUE   ex: LAB-0001 / RAD-0001
├── nama_tindakan      varchar(255)          ex: "Darah Rutin", "Rontgen Thorax"
├── bagian_id          FK → bagian           (pemilik layanan: Lab / Radiologi)
├── kategori           varchar(20) → 'LAB'|'RAD'|'LAIN'   (denormalisasi dr bagian)
├── satuan_hasil       varchar(20) nullable  ex: mg/dL, %, mm/jam (untuk lab)
├── nilai_normal       varchar(100) nullable ex: "L 13-17 / P 12-16" (referensi rujukan)
├── kode_bpjs          varchar(20) nullable  (kode/tarif VClaim MBG — tahap bridging)
├── kode_inacbg        varchar(20) nullable  (kode INACBG — tahap bridging)
├── kode_loinc         varchar(20) nullable  (LOINC utk lab — interop/SATU SEHAT)
├── keterangan         text nullable
└── audit + status_batal (input_time/mod_time/input_user_id/mod_user_id)

tindakan_harga
├── tindakan_harga_id  (PK, auto-increment)
├── tindakan_id        FK → tindakan
├── kelas_ruang_id     FK → kelas_ruang  (Kelas 1/2/3, VIP, VVIP) -- NULL = tarif DEFAULT
├── tarif              decimal(12,2) default 0   (tarif rumah sakit per kelas)
├── tarif_bpjs         decimal(12,2) nullable    (persiapan bridging VClaim/INACBG)
└── audit + status_batal
    UNIQUE (tindakan_id, kelas_ruang_id)   -- dgn kelas_ruang_id NULL sbg tarif default
```

Catatan:

- **`tindakan.bagian_id` adalah pemilik tunggal** — cukup untuk kebutuhan "mapping tindakan ke setiap bagian". Bila nanti ada tindakan lintas bagian, ubah jadi pivot `tindakan_bagian` (di luar cakupan konsep ini).
- **Tarif disimpan per kelas** dengan baris `kelas_ruang_id = NULL` sebagai **tarif default** (fallback bila kelas spesifik belum diisi). Saat order, tarif disnapshot (`harga` di detail order) sehingga perubahan master tidak mengubah order lama.
- Kolom `kode_bpjs`/`kode_inacbg`/`kode_loinc` + `tarif_bpjs` sudah disiapkan sekarang (diisi opsional) agar **bridging VClaim / INACBG** tinggal memakai data ini.

### 5.2 Pembagian jasa medis (tahap lanjut — desain awal)

Untuk keperluan **pembagian jasa medis**, tambahkan tabel berikut hanya saat fitur jasa diimplementasikan (bukan di iterasi pertama):

```
tindakan_jasa_medis
├── tindakan_jasa_medis_id  PK
├── tindakan_id             FK → tindakan
├── profesi_id              FK → profesi (Dokter, Analis, Radiografer, ...)
├── jenis_jasa              varchar: 'PERSENTASE' | 'NOMINAL'
├── nilai_jasa              decimal(12,2)   (persen atau nominal)
└── audit + status_batal
    UNIQUE (tindakan_id, profesi_id)
```

Alternatif: hitung jasa saat order (per `order_*_detail`) supaya history jasa ikut transaksi. Disepakati pada tahap pembahasan jasa.

---

## 6. Skema Database Order (migration baru)

Dua pasang tabel, terpisah per jenis penunjang (mengikuti pola existing: `peresepan_obat`, `pemesanan` + detail, dst.). Alasan terpisah: kolom domain berbeda (lab: satuan/nilai_normal; rad: modalitas/kesan), dan akses per bagian lebih bersih. Bila ingin lebih generik, bisa digabung `order_penunjang` + `jenis` — TIDAK direkomendasikan.

### 6.1 `order_laboratorium`

```php
Schema::create('order_laboratorium', function (Blueprint $table) {
    $table->increments('order_laboratorium_id');
    // audit
    $table->smallInteger('status_batal')->nullable();

    $table->string('no_order', 30);                       // LAB-20260919-0001
    $table->integer('pasien_id');
    $table->integer('registrasi_id');
    $table->integer('registrasi_detail_id');              // detail ASAL (poli/ruang peminta)
    $table->integer('registrasi_detail_tujuan_id');       // detail BARU di bagian lab (dibuat saat order)
    $table->integer('bagian_asal_id');                    // poli/ruang perujuk
    $table->integer('bagian_tujuan_id');                  // bagian lab
    $table->integer('dokter_id')->nullable();             // pegawai peminta (utk jasa medis)
    $table->string('prioritas', 15)->nullable();          // BIASA / CITO / SEGERA
    $table->smallInteger('status_order')->default(0);     // 0 Menunggu, 1 Diproses, 2 Selesai, 3 Batal
    $table->timestamp('tanggal_order')->nullable();
    $table->timestamp('tanggal_terima')->nullable();
    $table->timestamp('tanggal_hasil')->nullable();
    $table->integer('petugas_pelaksana_id')->nullable();  // pegawai lab yg entri hasil
    $table->smallInteger('kelas_id')->nullable();         // snapshot hak kelas asal (utk tarif/billing)
    $table->integer('hak_kelas_id')->nullable();
    $table->text('keterangan')->nullable();

    $table->index('registrasi_id');
    $table->index('registrasi_detail_id');
    $table->index('registrasi_detail_tujuan_id');
    $table->index('bagian_tujuan_id');
    $table->index('status_order');
});
```

### 6.2 `order_laboratorium_detail`

```php
Schema::create('order_laboratorium_detail', function (Blueprint $table) {
    $table->increments('order_laboratorium_detail_id');
    $table->smallInteger('status_batal')->nullable();

    $table->integer('order_laboratorium_id');
    $table->integer('tindakan_id')->nullable();           // FK master (nullable utk data lama/custom)
    $table->string('nama_tindakan', 255);                 // snapshot nama
    $table->decimal('harga', 12, 2)->default(0);          // snapshot tarif per kelas saat order
    $table->string('satuan_hasil', 20)->nullable();       // snapshot dari tindakan
    $table->string('nilai_normal', 100)->nullable();      // snapshot range rujukan
    $table->text('hasil')->nullable();                    // hasil entri petugas
    $table->smallInteger('flag_abnormal')->nullable();    // 0 normal, 1 abnormal
    $table->smallInteger('status')->default(0);           // 0 belum hasil, 1 selesai
    $table->integer('petugas_id')->nullable();            // entri hasil

    $table->index('order_laboratorium_id');
    $table->index('tindakan_id');
});
```

### 6.3 `order_radiologi` (cermin 6.1)

```php
Schema::create('order_radiologi', function (Blueprint $table) {
    $table->increments('order_radiologi_id');
    $table->smallInteger('status_batal')->nullable();

    $table->string('no_order', 30);                       // RAD-20260919-0001
    $table->integer('pasien_id');
    $table->integer('registrasi_id');
    $table->integer('registrasi_detail_id');              // asal
    $table->integer('registrasi_detail_tujuan_id');       // baru di bagian rad
    $table->integer('bagian_asal_id');
    $table->integer('bagian_tujuan_id');
    $table->integer('dokter_id')->nullable();
    $table->string('prioritas', 15)->nullable();
    $table->smallInteger('status_order')->default(0);     // 0..3
    $table->timestamp('tanggal_order')->nullable();
    $table->timestamp('tanggal_terima')->nullable();
    $table->timestamp('tanggal_hasil')->nullable();
    $table->integer('petugas_pelaksana_id')->nullable();
    $table->smallInteger('kelas_id')->nullable();
    $table->integer('hak_kelas_id')->nullable();
    $table->text('keterangan')->nullable();

    $table->index('registrasi_id');
    $table->index('registrasi_detail_id');
    $table->index('registrasi_detail_tujuan_id');
    $table->index('bagian_tujuan_id');
    $table->index('status_order');
});
```

### 6.4 `order_radiologi_detail`

```php
Schema::create('order_radiologi_detail', function (Blueprint $table) {
    $table->increments('order_radiologi_detail_id');
    $table->smallInteger('status_batal')->nullable();

    $table->integer('order_radiologi_id');
    $table->integer('tindakan_id')->nullable();
    $table->string('nama_tindakan', 255);
    $table->decimal('harga', 12, 2)->default(0);
    $table->text('hasil')->nullable();                    // kesan/deskripsi hasil
    $table->smallInteger('flag_abnormal')->nullable();
    $table->smallInteger('status')->default(0);
    $table->integer('petugas_id')->nullable();

    $table->index('order_radiologi_id');
    $table->index('tindakan_id');
});
```

> Semua tabel memakai `increments()` PK (kompatibel MySQL/PostgreSQL) + pola audit `status_batal` — mengikuti konvensi seluruh skema app.

---

## 7. Alur Kerja End-to-End

### 7.1 Order di EMR (Dokter/Perawat)

```
Dashboard Pasien → Order → Laboratorium / Radiologi
      │
      ▼
EMR form (slug laboratorium / radiologi, controller EMR\Laboratorium|Radiologi)
      │  1. Pilih tindakan (dari master tindakan bagian lab/rad) + prioritas
      ▼
store() dalam satu transaksi:
      ├─ 1. HITUNG tarif: tindakan_harga utk kelas registrasi_detail ASAL
      │      (fallback tarif default kelas NULL)
      ├─ 2. CREATE registrasi_detail TUJUAN:
      │      registrasi_id sama, bagian_id = lab/rad, tgl_daftar=now,
      │      kelas_id/hak_kelas_id snapshot asal, terima_dari='INTERNAL', status_batal=0
      ├─ 3. INSERT order_laboratorium/order_radiologi (no_order baru, status 0 Menunggu)
      ├─ 4. INSERT detail order (snapshot nama_tindakan, harga, satuan, nilai_normal)
      ├─ 5. (opsional/iterasi 2) INSERT BillTemp utk detail tujuan — kebutuhan billing
```

**`registrasi_detail` baru** ini membuat order "terlihat" sebagai pelayanan di modul lain (mis. list pelayanan pasien, antrian), dan menjadi penanda bahwa pasien memiliki layanan penunjang — sesuai permintaan.

### 7.2 Entri hasil di Penunjang Medis

```
Penunjang Medis → Laboratorium → Daftar Pesanan Laboratorium
      │  filter: pilih bagian lab (session, spt Depo di Farmasi), status, tgl, pasien/no order
      ▼
detail order:
      ├─ Tindakan item (nama, nilai normal, satuan) + input HASIL + ceklis ABNORMAL
      ├─ Aksi: Terima (status 0→1, isi tanggal_terima) · Simpan Hasil · Selesai · Batal · Cetak LHU
      ▼
`finalize` (status 2 Selesai):
      ├─ update detail hasil + flag_abnormal + petugas_id
      ├─ order.status_order = 2, tanggal_hasil = now
      ├─ (iterasi 2) tulis riwayat ringkas ke emr/emr_detail (form hasil_laboratorium)
      └─ hasil otomatis terlihat di Dashboard Pasien (bacaan riwayat EMR form laboratorium)
```

### 7.3 Hasil dilihat dokter (kembali ke EMR)

Dashboard Pasien → Order → Laboratorium → menampilkan **riwayat order pasien** dengan status; untuk order `Selesai`, kolom hasil/abnormal ikut tampil (read). Dokter **tidak menulis hasil** — hanya membaca; akses tulis dijaga `AksesEhr`.

---

## 8. Integrasi EMR / Dashboard Pasien

### 8.1 Perubahan tabel `form`

| form_id | nama_form | slug | id_dash_menu | ri/rj/igd/mcu |
|---|---|---|---|---|
| 5 | Peresepan Obat → **Order Resep** | `peresepan_obat` → **`order_resep`** | `'3.3'` → **`'4.4'`** | 1 |
| **6** | Order Laboratorium | `laboratorium` | `'4.5'` | 1 |
| **7** | Order Radiologi | `radiologi` | `'4.6'` | 1 |

- `dashboard_menu` (baru): `4 = Order`.
- `dashboard_menu_sub`: `4 = Order Resep`, `5 = Laboratorium`, `6 = Radiologi` (semua `dashboard_menu_id=4`).
- Menu lama `dashboard_menu` 3 "Resep" + sub 3 di-`status_batal=1` (hindari menu kosong).
- **Migrasi slug `peresepan_obat` → `order_resep`** (lihat 8.3) — karena dashboard memakai nama sub sebagai `form_name`.

### 8.2 Controller & view EMR baru

- `App\Http\Controllers\EMR\Laboratorium\LaboratoriumController` — index/store/update/destroy
- `App\Http\Controllers\EMR\Radiologi\RadiologiController`
- View: `resources/views/moduls/EMR/Laboratorium/index.blade.php`, `moduls/EMR/Radiologi/index.blade.php`
- Di-resolve otomatis oleh `DynamicFormController` (pola `Str::studly($form_name)`), jadi **tidak perlu route manual**; redirect setelah simpan pakai `route('emr.dynamic.index', ['form_name' => 'laboratorium'|'radiologi', ...])`.

View EMR menampilkan:

1. Form order baru (dropdown tindakan per bagian, prioritas, keterangan) hanya bila `$aksesCrud['create']`.
2. Riwayat order dari `order_laboratorium`/`order_radiologi` (bukan `emr` table) — filter `registrasi_id`, join detail + petugas + hasil; status badge (Menunggu/Diproses/Selesai/Batal).
3. Untuk order Selesai, render hasil (lab: tiap item nilai+normal/abnormal; rad: teks hasil/kesan).

### 8.3 Migrasi slug peresepan (dampak & langkah)

Karena sub menu dashboard harus = slug form:

1. `form.slug`: `peresepan_obat` → `order_resep` (form 5), `id_dash_menu` → `'4.4'`.
2. Rename controller `EMR/PeresepanObat/PeresepanObatController.php` → `EMR/OrderResep/OrderResepController.php`.
3. Rename view `EMR/PeresepanObat/index.blade.php` → `EMR/OrderResep/index.blade.php`.
4. Ganti semua `EmrHelper::formIdBySlug('peresepan_obat')` dan `route('emr.dynamic.index', [... 'peresepan_obat' ...])` di `OrderResepController`/view ke slug baru.
5. `EmrMasterSeeder` diperbarui (dashboard menu 4, form 5/6/7, akses).
6. **Data `emr` lama tidak perlu migrasi** — `emr.form_id` (angka) tetap 5, tidak menyimpan slug; aman.
7. Module Farmasi (List Pesanan Resep) & depo **tetap memakai `peresepan_obat`** (tabel, model) — hanya identitas EMR (slug) yang berubah; `no_resep`, dispense, dsb. tidak terpengaruh.

> Jika ingin **menghindari rename** (risiko lebih rendah), alternatif: sub tetap bernama `Peresepan Obat` dalam menu **Order** → dashboard tampil "Order → Peresepan Obat". Pilih sesuai selera; konsep ini memilih rename agar label persis **"Order Resep"** sesuai permintaan.

### 8.4 Akses EHR

- `akses_ehr` untuk form 6 (lab): profesi **Dokter** (1) create+read+update+delete; **Perawat** (2) read; **Analis Laboratorium** (13, baru) read.
- `akses_ehr` untuk form 7 (rad): profesi **Dokter** create+read+update+delete; **Perawat** read; **Radiografer** (5) read.
- *Entri hasil* TIDAK lewat `emr.dynamic.*` sehingga tidak bergantung akses EHR form — dikendalikan via `user_akses` sidebar modul Penunjang Medis.

---

## 9. Daftar Pesanan (Input Hasil) — Detail Rancangan

Controller: `DaftarPesananLaboratoriumController` & `DaftarPesananRadiologiController` (pola meniru `ListPesananResepController`).

- **Pilih bagian aktif (session)** seperti Depo di Farmasi: dropdown bagian `referensi_bagian_id=6` → `session('lab_bagian_id')` / `session('rad_bagian_id')`.
- **Filter daftar**: no_order, pasien, tgl_awal–akhir, jenis_rawat, status.
- **Aksi** (route manual di `routes/web.php`, mengikuti pola route non-CRUD existing):

  | Route | Method |
  |---|---|
  | `POST /daftar_pesanan_laboratorium/pilih-bagian` | pilih bagian lab (session) |
  | `POST /order_laboratorium/{id}/terima` | terima order → status 1, tanggal_terima |
  | `POST /order_laboratorium/{id}/simpan-hasil` | simpan hasil per detail (parsial) |
  | `POST /order_laboratorium/{id}/selesai` | finalisasi → status 2, tanggal_hasil |
  | `POST /order_laboratorium/{id}/batal` | batal → status 3 |
  | `GET  /order_laboratorium/{id}/cetak` | cetak LHU (Laporan Hasil Uji) |
  | (sama untuk `order_radiologi/{id}/...`) | |

- **Validasi `status_order`**: hanya status `0` Menunggu yang bisa diubah/ dibatalkan; hasil hanya bisa diinput saat status `1` Diproses (atau langsung 0→2 jika mekanisme singkat diinginkan). Detail hanya bisa diedit sebelum `Selesai`.
- **Fungsi utama pindah ke helper** agar dipakai lintas controller (`EMR\Laboratorium`, `EMR\Radiologi`, `DaftarPesanan*`, `MasterTindakan`):

  `App\Helpers\PenunjangHelper`
  - `generateNoOrder(string $prefix, string $table, string $column)` → `"LAB-20260919-0001"` / `"RAD-..."` (pola `generateNoResep` existing).
  - `tarif(int $tindakanId, ?int $kelasBpjsId)` → ambil `tindakan_harga` per `kelas_ruang_id`, fallback default `NULL`.
  - `buatOrder(...)` → satu transaksi: hitung tarif, create `registrasi_detail` tujuan, insert header+detail order.
  - `simpanHasil(...)`, `finalisasi(...)`, `batalkan(...)`, `daftarOrder(...)`, `riwayatOrderPasien($registrasiId, $jenis)`.
  - `kelasRuangId(int $hakKelasId)` / resolusi tarif via snapshot.

### 9.1 Penomoran

```
GenerateHelper (extend) atau PenunjangHelper::generateNoOrder:
prefix = 'LAB' | 'RAD' + '-' + Ymd
LAB-20260919-0001, LAB-20260919-0002, ... (4 digit, reset per hari)
```

---

## 10. Pegawai, Profesi & User

Update `MasterPegawaiSeeder`:

- Profesi baru: `profesi_id = 13` → **Analis Laboratorium**.
- Pegawai baru (contoh): `pegawai_id = 13` "Analis Laboratorium" `profesi_id=13`, semua pegawai penunjang memakai `bagian_id` lab/rad (`36`/`37`).
- User baru (opsional, di `UserSeeder`): analis & radiografer diberi akses sub_menu 44/45 (sidebar Penunjang) + form terkait.
- `UserSeeder` menghitung sub_menu aktif secara dinamis → sub_menu/modul baru otomatis terakses admin.

---

## 11. Seeding — Ringkasan Perubahan

| Seeder | Perubahan |
|---|---|
| `ReferensiBagianSeeder` | tambah `6 = PENUNJANG MEDIS` |
| `BagianSeeder` | append `INSTALASI LABORATORIUM` (36), `INSTALASI RADIOLOGI` (37) |
| `MasterPegawaiSeeder` | profesi 13 + pegawai Analis Laboratorium (id 13) |
| `ModulMenuSubMenuSeeder` | modul 8, menu 14/15/16, sub 44/45/46 (lalu jalankan `seeder:sync-master-menu`) |
| `EmrMasterSeeder` | dashboard_menu 4 "Order", sub 4/5/6, form 5 (rename slug/`id_dash_menu`), form 6/7, objek & mapping opsional, akses_ehr |
| `TindakanSeeder` (baru) | contoh master: lab (DARAH RUTIN, URIN LENGKAP, GLUKOSA DARAH, SGOT/SGPT, KREATININ, MCU); rad (RONTGEN THORAX, RONTGEN EKSTREMITAS, USG ABDOMEN, CT SCAN KEPALA) + `tindakan_harga` per kelas 1/2/3/VIP/VVIP |
| `DatabaseSeeder` | panggil `TindakanSeeder`; tambah tabel baru ke `GenerateHelper::resetSequence()` |

**Waspada:** `BagianSeeder` menghapus seluruh `bagian` lalu seed ulang — pastikan all references (`pegawai.bagian_id`, `registrasi_detail.bagian_id`, jadwal, dst.) sudah disesuaikan/backup sebelum `migrate:fresh --seed`.

---

## 12. Siap VClaim / INACBG & Jasa Medis (Rencana Tahap Lanjut)

Sesuai desain `tindakan` / `tindakan_harga`:

1. **Bridging VClaim (klaim BPJS):**
   - `tindakan.kode_bpjs` & `tindakan_harga.tarif_bpjs` → cocokkan saat klaim/INACBG.
   - `order_*_detail` membawa snapshot `harga` (tarif RS) & `registrasi_detail_tujuan_id` (kelas) → siap dihubungkan ke `RujukanSep`/registrasi saat bridging.
2. **Pembagian jasa medis:**
   - Tabel `tindakan_jasa_medis` (5.2) menentukan porsi per profesi; atau snapshot `jasa` saat order (`order_*_detail`).
   - Kolom `dokter_id` (peminta) & `petugas_pelaksana_id` di header order otomatis menjadi dasar pembagian jasa.
3. **SATU SEHAT / interoperabilitas:** `kode_loinc` di `tindakan` + kolom `id_satu_sehat` di `emr_detail` (sudah ada) untuk export hasil.

---

## 13. Risiko, Konsekuensi & Keputusan yang Harus Disepakati

1. **Rename slug `peresepan_obat` → `order_resep`** — breaking change internal; hanya menyentuh `form`/controller/view/`emr.dynamic.index` redirect, AMAN terhadap data (emr pakai `form_id`). Alternatif tanpa rename: sub menu tetap `Peresepan Obat`.
2. **Dashboard menu "Resep" (lama) dihapus** — pastikan tidak ada referensi `id_dash_menu '3.3'` selain form 5.
3. **`id_dash_menu` harus cocok dengan id aktual `header_ehr`** — id dashboard_menu/sub/extra yang sudah ada di DB menentukan string id; jangan hardcode di luar seeder (`updateOrInsert`, idempotent).
4. **Dua `registrasi_detail` per kunjungan** (asal + tujuan lab/rad) — pastikan billing tidak dobel; `status_batal`/flag selesai dipakai untuk kontrol. Iterasi billing (BillTemp untuk detail tujuan) baru di tahap 2.
5. **Tarif snapshot** — master tindakan boleh berubah, order lama tidak ikut berubah.
6. **Bagian penunjang wajib ber-`referensi_bagian_id=6`** — filter daftar pesanan & dropdown tindakan memakai ini; jangan `whereNull`.
7. **Kelas tarif** memakai `kelas_ruang` dari `KelasRuang::aktif()` dan `hak_kelas_id` asal — jangan pakai `SelectOption::get('kelas_perawatan')` (sudah dihapus).
8. **Akronim folder** — `Laboratorium`, `Radiologi`, `MasterTindakan`, `DaftarPesananLaboratorium` adalah kata utuh (bukan `Lab`/`Rad`) agar basename/URI konsisten.
9. **Route aksi non-CRUD** (pilih bagian/terima/simpan hasil/selesai/batal/cetak) ditulis manual di `routes/web.php` — auto-router hanya mengurus CRUD sub_menu.

---

## 14. Rencana Implementasi Bertahap

### Iterasi 1 (inti — sesuai konsep ini)
1. Migration: `referensi_bagian`+`bagian` (via seeder), `tindakan`, `tindakan_harga`, `order_laboratorium(+detail)`, `order_radiologi(+detail)`.
2. Seeder: Referensi/Bagian/MasterPegawai + `TindakanSeeder`.
3. Master Tindakan CRUD (form tarif per kelas via grid `KelasRuang::aktif()`).
4. Order di EMR: `dashboard_menu` Order + form slug `laboratorium`/`radiologi` (rencana slug `order_resep`) + controller & view EMR.
5. Daftar Pesanan Lab/Rad: list, terima, input hasil, selesai, batal, cetak draft.
6. `PenunjangHelper`, `GenerateHelper::generateNoOrder`, akses & user.
7. `ModulMenuSubMenuSeeder` + `seeder:sync-master-menu` + sidebar cache clear.

### Iterasi 2 (penguatan)
- BillTemp linkage utk detail tujuan; snapshot jasa medis per order.
- `emr`/`emr_detail` mirror hasil (interop SATU SEHAT / "hasil muncul di list EMR generik").
- Bridging VClaim (mapping `kode_bpjs`/INACBG), LHU resmi (PDF), notifikasi status.
- `tindakan_jasa_medis` & pembagian jasa.

---

## 15. Referensi Kode Existing yang Dipakai

- `App\Helpers\EmrHelper` — form lookup, emrList, dinamika form EMR.
- `App\Helpers\AksesEhr` — gate CRUD form EMR (`can`/`flags`).
- `App\Helpers\GenerateHelper` — `generateNoUrut`, `resetSequence` (perlu tambah `generateNoOrder`).
- `App\Http\Controllers\EMR\PeresepanObat\PeresepanObatController` — pola order EMR (status order, detail, redirect).
- `App\Http\Controllers\Farmasi\Resep\ListPesananResep\ListPesananResepController` — pola "daftar pesanan + bagian aktif session + aksi".
- `Administrator\ManajemenMaster\Kelas\KelasController` & `NasabahController` — pola CRUD validasi `array_merge` default.
- `EmrDashboardController` & view `header_ehr` — aturan kemunculan form di dashboard pasien.
- `ModulMenuSubMenuSeeder` + `seeder:sync-master-menu` — daftar modul/menu/sub_menu.