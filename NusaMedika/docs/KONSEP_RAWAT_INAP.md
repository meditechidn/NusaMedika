# KONSEP MODUL RAWAT INAP (BED MANAGEMENT + MASTER BED)

> **Pembaruan:** tabel `bill_temp` dan model `BillTemp` sudah dihapus. Billing disatukan di tabel `billing` (penanda selesai = `billing.status_selesai`). Referensi `bill_temp` di bawah adalah konteks historis.

> Status: **Rancangan / Konsep** — belum diimplementasikan.
> Dokumen ini menjadi acuan implementasi. Semua nama (folder, route, sub_menu, sub_menu_id, kolom baru) bersifat keputusan awal dan harus disepakati sebelum kode dibuat. Keputusan user yang sudah dikunci: (1) status **Persiapan Pulang** = toggle manual di Bed Management; (2) **Daftar Rawat Inap dilengkapi store** sebagai sumber alur masuk ranap & waitlist; (3) **seed bed contoh** di seeder.

---

## 1. Ringkasan

Modul **Rawat Inap** saat ini baru berupa halaman landing (`ListPasienRanap` query-nya rusak karena env kosong, `DaftarRanap` belum bisa menyimpan, dan belum ada pengelolaan tempat tidur). Iterasi 1 menjadikan Rawat Inap **berfungsi end-to-end**:

- **Perbaiki env & list pasien ranap**: tambah konstanta `REF_BAGIAN_RANAP=2` (ruang perawatan), sehingga `ListPasienRanapController` dan komponen `SelectRuangPerawatan` kembali menampilkan ruang & pasien.
- **Daftar Rawat Inap tersimpan**: lengkapi `DaftarRanapController::store` (admis pasien ke ruang perawatan, `jenis_rawat=RI`, billing via `bill_temp`) — menjadi sumber data pasien yang menunggu bed (**waitlist**).
- **Master Bed (baru, Administrator)**: CRUD master tempat tidur per ruang — dasar dari Bed Management.
- **Bed Management (baru, Rawat Inap → Pasien)**: visualisasi status bed per ruang (**Kosong / Terisi / Persiapan Pulang**) + panel **Waitlist** untuk menempatkan pasien ke bed, menandai "persiapan pulang", dan melepas bed.
- **Tabel `bed`** (legacy, sejauh ini 0 baris, tanpa Model) menjadi unit kerja utama; ditambahkan kolom `flag_persiapan_pulang` + `tgl_pulang`.

Alur data satu siklus ranap:

```
DaftarRanap.store  →  registrasi (jenis_rawat=RI) + registrasi_detail (bagian=ruang, kelas) + bill_temp
      │  (pasien masuk waitlist — belum punya bed)
      ▼
BedManagement.assign  →  bed.pasien_id_1 = pasien, bed.tgl_masuk = hari ini, bed.kelas = kelas ruang
      │
      ▼
BedManagement.ready   →  bed.flag_persiapan_pulang = 1 + tgl_pulang (rencana)
      │
      ▼
BedManagement.release →  bed dikosongkan (pasien_id_1 = null) — pasien sudah keluar dari ranap
```

---

## 2. Tujuan & Manfaat

| Tujuan | Manfaat |
|---|---|
| `REF_BAGIAN_RANAP` terisi benar (=2) | List Pasien Ranap & filter ruangan kembali berfungsi (bug env `31` dihapus) |
| Daftar Ranap bisa menyimpan | Petugas bisa menadmis pasien langsung dari kunjungan IGD/poli/rujukan; data mengalir ke billing |
| Master Bed di Administrator | Tempat tidur dikelola terpusat (nomor kamar, nama bed, kelas, isolasi, ventilator, neonatus) |
| Bed Management di Rawat Inap | Petugas melihat status seluruh bed per ruang dalam satu layar: kosong/terisi/persiapan pulang |
| Status "Persiapan Pulang" manual | Petugas menandai bed yang akan bebas — membantu alur siapa yang bisa pindah bed/ruang |
| Seed bed contoh | Data siap pakai untuk demo & uji alur tanpa input manual |

---

## 3. Struktur Modul & Sidebar

```
Rawat Inap               (modul 2, urutan_modul 5)
└── Pasien
     ├── List Pasien Ranap                       (sub existing)
     └── Bed Management                          (sub_menu_id 52)  ← BARU
         file_sub_menu = RawatInap/Pasien/BedManagement/bed_management

Administrator            (modul 5, urutan_modul 7)
└── Manajemen Master
     └── Master Bed                              (sub_menu_id 51)  ← BARU
         file_sub_menu = Administrator/ManajemenMaster/Bed/bed
```

- Leaf harus **kata utuh tanpa akronim**: `BedManagement`, `MasterBed` → basename `bed_management` / `bed`.
- Controller:
  - `App\Http\Controllers\Administrator\ManajemenMaster\Bed\BedController` (CRUD, route `admin.bed.*`, URI `/bed`).
  - `App\Http\Controllers\RawatInap\Pasien\BedManagement\BedManagementController` (index + aksi assign/ready/release).
- Views: `moduls/administrator/manajemen_master/bed/{bed,bed_create,bed_edit}.blade.php` dan `moduls/RawatInap/Pasien/BedManagement/bed_management.blade.php`.
- Route Bed Management: `bed_management.index` (auto dari sub 52) + **route manual POST** `/bed_management/assign|ready|release` (aksi non-CRUD → `routes/web.php`, lihat §11).
- Tampilan sidebar pasca-implementasi:

```
Rawat Inap (modul 2)
 └── Pasien
      ├── List Pasien Ranap
      └── Bed Management      ← BARU
...
Administrator (modul 5)
 └── Manajemen Master
      └── Master Bed          ← BARU
```

---

## 4. Tabel `bed` (existing legacy + 2 kolom baru)

Skema `2026_08_28_000039_create_bed_table` sudah ada (tanpa Model). Kolom yang relevan:

| kolom | tipe | keterangan |
|---|---|---|
| `bed_id` | increments PK | auto-increment |
| `bagian_id` | int | ruang perawatan (bagian ref `RAWAT_INAP`) |
| `no_kamar` | string | nomor kamar |
| `nama_bed` | string | nama bed (mis. "B-01") |
| `status_bed` | string | legacy, bisa dipakai indikator awal |
| `kelas_id` | int | kelas ruang (`kelas_ruang`) |
| `tgl_masuk` | datetime nullable | waktu pasien menempati bed |
| `pasien_id_1` | int nullable | pasien aktif di bed |
| `pasien_id_2` | int nullable | (cadangan/kelas 1) |
| `flag_isolasi` | smallint | 1=Isolasi, 2=Normal, 3=Covid |
| `flag_ventilator` / `flag_neonatus` / `siap_kirim` | smallint | penanda sarana |
| `lokasi` | string nullable | label lokasi bed |
| `kodekelas` / `namakelas` | string | snapshot kelas (legacy) |
| `input_time`/`mod_time`/`input_user_id`/`mod_user_id` | timestamp(6)/int | audit |
| `status_batal` | smallint nullable | soft-delete |

### 4.1 Kolom baru (migration)

Migration baru (mis. `2026_09_28_000001_add_persiapan_pulang_to_bed_table`):

| kolom | tipe | keterangan |
|---|---|---|
| `flag_persiapan_pulang` | smallint nullable | 0/1 — status "Persiapan Pulang" (toggle manual) |
| `tgl_pulang` | datetime nullable | tanggal/jam rencana pulang |

> Kolom `status_bed` legacy dibiarkan; status sumber kebenaran di UI dihitung dari `pasien_id_1` + `flag_persiapan_pulang` (lihat §7).

### 4.2 Model `Bed`

```php
class Bed extends Model
{
    protected $primaryKey = 'bed_id';
    public $timestamps = false;
    // relasi bagian(), kelas() (bagian_id → bagian, kelas_id → kelas_ruang)
    // scope aktif() (status_batal != 1 OR NULL)
    // scope terisi() → whereNotNull('pasien_id_1')
    // scope kosong() → whereNull('pasien_id_1')
}
```

---

## 5. Konstanta `.env` — `REF_BAGIAN_RANAP`

Tambah di `.env` **dan** `.env.example`:

```ini
REF_BAGIAN_RANAP=2
```

- `referensi_bagian=2` = RAWAT INAP (ruang perawatan bagian 16–28).
- **Bug yang langsung ke-fix**: `ListPasienRanapController` baris 50 & 128 memakai `env('REF_BAGIAN_RANAP', 31)` (fallback salah), dan `SelectRuangPerawatan` (baris 27) memakai `env('REF_BAGIAN_RANAP')` yang kosong → dropdown ruangan kosong.
- Setelah edit `.env` wajib recreate container: `docker compose up -d --force-recreate app vite`.
- Konvensi nama sama dengan `REF_BAGIAN_RAJAL=1` yang sudah ada.

---

## 6. Daftar Rawat Inap — `DaftarRanapController::store`

Saat ini `DaftarRanapController` hanya `index()` (landing + daftar ruang/kelas). Iterasi 1 menambahkan `store()` — mirror `DaftarRajalController` **tanpa** bagian jadwal/antrian (ranap tidak pakai `registrasi_urut`):

### 6.1 `index()`

- Kirim `$ruangs` (bagian ref `REF_BAGIAN_RANAP` aktif), `$kelasList` (`KelasRuang::aktif()` — hati-hati yang seed ber-`status_batal=0`, jangan `whereNull` saja), `$nasabahs` (penjamin aktif).
- Relasi dropdown `asal_pasien_ranap` (dari `SelectOption` — key sudah ada: IGD / Poliklinik / Rujukan Luar).

### 6.2 `store()` — satu transaksi

Validasi: `pasien_id`, `tgl_masuk` (date), `bagian_id` (ruang, exists bagian), `kelas_id` (opsional → fallback hak kelas nasabah), `nasabah_id`, `asal_pasien_ranap`, diagnosa opsional (`icd_id`).

Insert (mirror DaftarRajal):
1. `PasienNasabah` find-or-create.
2. `Registrasi`: `tgl_masuk=datetime`, **`jenis_rawat = env('JENIS_RAWAT_RI','RI')`**, `prioritas=asal_pasien_ranap`, `pasien_nasabah_id`, `memo` (keluhan/diagnosa awal opsional).
3. `RegistrasiDetail`: `bagian_id=ruang`, snapshot `kelas_id`/`hak_kelas_id` dari `KelasRuang` (fallback hak kelas nasabah), `terima_dari = 'DALAM'`, `check_in = tgl_masuk`, `tgl_daftar`.
4. `BillTemp`: snapshot kelas/nasabah/bagian, `status_selesai=0`, `bill_temp_jenis` (contoh `'RANAP'`).
5. `DiagnosaRawat` (opsional bila ICD diisi).
6. `PenanggungRawat`: `rawat_user_id` = dokter penanggung jawab (opsional, fallback user login).

Cek duplikat aktif per pasien (pasien belum punya registrasi RI aktif) → tolak bila ada. Flash sukses → redirect ke **`list_pasien_ranap.index`**.

> **Integrasi IGD → Ranap**: bila `asal_pasien_ranap='IGD'` dan pasien berasal dari registrasi IGD aktif, set `check_out` pada `registrasi_detail` IGD tsb (dan lepas bed IGD bila ada) — dikoordinasikan dengan Iterasi Gawat Darurat.

---

## 7. Bed Management — `BedManagementController` (Rawat Inap → Pasien)

### 7.1 `index()` — satu layar status bed + waitlist

- Parameter `?ruang_id=` (wajib; bila kosong default ruang pertama ref `REF_BAGIAN_RANAP`).
- Daftar **bed aktif** ruang tsb; setiap bed dihitung statusnya:
  - **Kosong** — `pasien_id_1 IS NULL`.
  - **Terisi** — `pasien_id_1` ada, `flag_persiapan_pulang` 0/null.
  - **Persiapan Pulang** — `pasien_id_1` ada, `flag_persiapan_pulang=1` (+ `tgl_pulang`).
- **Panel Waitlist**: registrasi `jenis_rawat=RI` aktif (status_batal filter) **tanpa** bed (`bed.pasien_id_1` kosong) di ruang tsb — dari `registrasi`/`registrasi_detail` (asosiasi bed lewat `bagian_id`) + join pasien/nasabah.
- Ikut kirim `$kelasMap` (`KelasRuang::aktif()`) untuk label kelas bed.

### 7.2 Aksi (`assign` / `ready` / `release`) — route manual POST

| route | aksi |
|---|---|
| `POST /bed_management/assign` | tempatkan pasien waitlist ke bed: `bed.pasien_id_1 = registrasi.pasien_id`, `tgl_masuk = now`, `kelas_id`/`kodekelas`/`namakelas` disnapshot dari registrasi_detail |
| `POST /bed_management/ready` | toggle Persiapan Pulang: `flag_persiapan_pulang=1` + `tgl_pulang` (input datetime/opsional) |
| `POST /bed_management/release` | kosongkan bed: `pasien_id_1=null`, `pasien_id_2=null`, `tgl_masuk=null`, `flag_persiapan_pulang=null`, `tgl_pulang=null` |

Semua aksi aktif-filter `status_batal`, dalam `DB::beginTransaction()`, validasi `bed_id` (exists, aktif) dan untuk assign `registrasi_detail_id` (aktif, jenis_rawat=RI, bagian=ruang bed).

> `flag_persiapan_pulang` = **toggle manual** (keputusan user): tidak ada trigger otomatis dari billing/klinik; petugas yang menandai.

---

## 8. Master Bed — `BedController` (Administrator → Manajemen Master)

CRUD standar pola `KelasController`/`IcdController`:

- `index()` — list aktif dengan **filter `bagian_id`** (dropdown ruang ref `REF_BAGIAN_RANAP`); kolom: Kamar, Nama Bed, Ruang, Kelas, Isolasi, Ventilator/Neonatus, Status (dari `pasien_id_1`), Aksi.
- `store()/update()` — validasi: `bagian_id` (exists, wajib), `no_kamar` (wajib), `nama_bed` (wajib), `kelas_id` (exists `kelas_ruang`, opsional), `flag_isolasi` (1/2/3), `flag_ventilator`, `flag_neonatus`. Tulis dalam `DB::beginTransaction()`; PK auto-increment; `clearSidebarCache` semua user.
- `destroy()` — soft-delete (`status_batal=1`); tolak bila bed terisi (pasien masih menempati).
- Dropdown ruang memakai **`x-select-ruang-perawatan`** (component) + dropdown kelas `KelasRuang::aktif()` — **jangan** `SelectOption::get('kelas_perawatan')` (sudah dihapus).

### 8.1 Seeder `BedSeeder` (data contoh)

- ±4 bed per ruang perawatan bagian 16–28 (≈52 bed), id berurutan, `nama_bed` pola `B-01`…`B-04`, `no_kamar` pola `R{16..28}-0{n}`, kelas mengikuti kelas ruang (bagian 16–20 Kelas 3, dll. — sesuai `namakelas`), `flag_isolasi=2` (Normal) default.
- Daftarkan di `DatabaseSeeder` (setelah `KelasRuangSeeder`/`BagianSeeder`) + tambah `bed` ke `GenerateHelper::resetSequence()`.
- Idempotent (`updateOrInsert` key `bed_id`); seeder `MENU_MAX`/`SUBMENU_MAX` tidak dipakai — id bed dari seeder eksplisit lalu urut.

---

## 9. Perubahan pada Fitur Existing

1. **`ListPasienRanapController`** — biarkan query (tetap join `bed` via `r.pasien_id = bd.pasien_id_1`), karena env `REF_BAGIAN_RANAP=2` otomatis memperbaiki filter ruang. (Opsional: gunakan `EmrHelper` pada konsultasi di iterasi lanjutan — catatan AGENTS §Legacy Konsultasi.)
2. **`SelectRuangPerawatan`** — bekerja setelah env terisi; tanpa kode baru.
3. **`x-select-ruang-perawatan`** — dipakai di Daftar Ranap & Bed Management (filter `referensi_bagian_id = REF_BAGIAN_RANAP`).
4. Tidak mengubah `DaftarRajal`; ranap terpisah karena `jenis_rawat=RI`.

---

## 10. Seeding — Ringkasan Perubahan

| Seeder | Perubahan |
|---|---|
| `ModulMenuSubMenuSeeder` | sub 51 "Master Bed" (`Administrator/ManajemenMaster/Bed/bed`) di menu 6 (Manajemen Master); sub 52 "Bed Management" (`RawatInap/Pasien/BedManagement/bed_management`) di menu Rawat Inap → Pasien; lalu `php artisan seeder:sync-master-menu` |
| `BedSeeder` (baru) | seed ±52 bed (4 × ruang 16–28, `flag_isolasi=2`) |
| `UserSeeder` | admin otomatis mendapat sub 51/52 (dinamis) — tanpa ubah manual |
| `.env` / `.env.example` | tambah `REF_BAGIAN_RANAP=2` |

**Migration baru (1):**

`2026_09_28_000001_add_persiapan_pulang_to_bed_table` — `flag_persiapan_pulang` (smallint nullable) + `tgl_pulang` (datetime nullable) pada `bed`.

---

## 11. Route

- **Master Bed** — auto dari sub 51: `admin.bed.*` (index/create/store/edit/update/destroy), URI `/bed`.
- **Bed Management** — `bed_management.index` auto dari sub 52 (controller hanya method `index`).
- **Aksi assign/ready/release** — **route manual** di `routes/web.php` (bukan CRUD, reusable):

```php
Route::post('/bed_management/assign',  [BedManagementController::class, 'assign'])->name('bed_management.assign');
Route::post('/bed_management/ready',   [BedManagementController::class, 'ready'])->name('bed_management.ready');
Route::post('/bed_management/release', [BedManagementController::class, 'release'])->name('bed_management.release');
```

> Route manual yang terdaftar lebih dulu **selalu menang** atas route auto yang bentrok.

---

## 12. Risiko, Konsekuensi & Keputusan yang Harus Disepakati

1. **`REF_BAGIAN_RANAP` adalah bug blocker** — tanpa env ini List Pasien Ranap & dropdown ruangan rusak (`31` ≠ `2`). Dikerjakan pertama & direcreate container.
2. **`bed` di-occupancy by `pasien_id_1`** — alokasi 1 pasien/bed (ranap dasar). `pasien_id_2` disisakan untuk kelas 1/dua pasien di iterasi lanjutan.
3. **Seeding bed menghapus data lama?** — `bed` legacy 0 baris sehingga aman. Umumnya `BedSeeder` memakai `updateOrInsert` + `resetSequence` agar tidak bentrok dengan bed buatan user lewat Master Bed.
4. **"Persiapan Pulang" bukan status medis** — murni operasional (toggle manual). Tidak memengaruhi `bill_temp.status_selesai`; keluar ranap (status billing & `tgl_keluar`) di iterasi lanjutan.
5. **Satu pasien = satu bed aktif** — saat assign, pasien yang sudah terdaftar bed di ruang lain di-tolak (atau auto-release bed lama) — diputuskan saat implementasi.
6. **`bed.body status_bed` legacy dibiarkan** — sumber kebenaran status UI dari `pasien_id_1` + `flag_persiapan_pulang`, bukan kolom string.
7. **sub_menu_id 51/52 bebas saat dokumen ini ditulis** — cek ulang sebelum migrate/seed (pasti `MENU_MAX`/`SUBMENU_MAX` masih).
8. **Clea cache** — setelah seeding menu & edit sub_menu: `php artisan route:clear && cache:clear` + `SidebarComposer` clear via `SubMenuController` (auto saat sub_menu diubah).

---

## 13. Rencana Implementasi Bertahap

### Iterasi 1 (inti — ranap berfungsi + bed)
1. `.env` + `.env.example`: `REF_BAGIAN_RANAP=2`; recreate container.
2. Migration `flag_persiapan_pulang`/`tgl_pulang` di `bed` + Model `Bed`.
3. `BedSeeder` (±4/ruang 16–28) + daftar di `DatabaseSeeder`/`resetSequence`.
4. `BedController` + 3 views (CRUD `admin.bed.*`, filter ruang) + sub_menu 51.
5. `DaftarRanapController::store` + form di `daftar_ranap.blade.php` (route `daftar_ranap.store`).
6. `BedManagementController` (index + assign/ready/release) + view `bed_management.blade.php` + route manual.
7. `ModulMenuSubMenuSeeder` (sub 51–52) + `seeder:sync-master-menu` + clear cache.
8. `migrate:fresh --seed` + `route:list` + smoke test HTTP + pint PASS.

### Iterasi 2 (penguatan)
- Keluar ranap: set `registrasi.tgl_keluar` + `bill_temp.status_selesai` + auto-release bed.
- Pindah bed/ruang, bed dua pasien (kelas 1), alur IGD → Ranap otomatis (`check_out` IGD).
- Integrasi billing per hari (`inpatient days` — lanjut dari `ListPasienRanapController` yang sudah menghitung durasi).

---

## 14. Referensi Kode Existing yang Dipakai

- `App\Http\Controllers\Registrasi\Pendaftaran\DaftarRajal\DaftarRajalController` — blueprint store pendaftaran (diadaptasi tanpa jadwal/urutan).
- `App\Http\Controllers\RawatInap\Pasien\ListPasienRanap\ListPasienRanapController` — sandi query ranap + durasi rawat (wajib di-fix env terlebih dulu).
- `App\View\Components\SelectRuangPerawatan` — dropdown ruang (bekerja setelah env terisi).
- `App\Http\Controllers\Administrator\ManajemenMaster\Kelas\KelasController` (+ `IcdController`) — blueprint CRUD master.
- `App\Models\KelasRuang` — `aktif()`, label kelas; `App\Models\Registrasi/RegistrasiDetail/BillTemp/PasienNasabah` — reuse.
- `database/migrations/2026_08_28_000039_create_bed_table.php` — skema bed legacy (dasar, + 2 kolom).
- `App\Helpers\GenerateHelper` — `resetSequence()` untuk `bed`.