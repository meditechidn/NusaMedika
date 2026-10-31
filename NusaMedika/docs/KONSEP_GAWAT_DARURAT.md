# KONSEP MODUL GAWAT DARURAT (IGD)

> **Pembaruan:** tabel `bill_temp` dan model `BillTemp` sudah dihapus. Billing disatukan di tabel `billing` (penanda selesai = `billing.status_selesai`). Referensi `bill_temp` di bawah adalah konteks historis.

> Status: **Rancangan / Konsep** — belum diimplementasikan.
> Dokumen ini menjadi acuan implementasi. Semua nama (folder, route, sub_menu, kolom baru) bersifat keputusan awal dan harus disepakati sebelum kode dibuat. Keputusan user yang sudah dikunci: (1) **Pasien Darurat (Mr. X)** dibuat sederhana (pasien tanpa identitas lengkap); (2) **Daftar IGD Obgyn ikut dilengkapi** pada iterasi yang sama; (3) **Ruangan/Zona IGD pakai dropdown statis** (`SelectOption::get('ruang_igd')`) yang disimpan di `registrasi_detail.lokasi_rawat`.

---

## 1. Ringkasan

Modul **Gawat Darurat** saat ini berupa halaman landing statis: `DaftarGawatDarurat` form-nya `action="#"` (tidak tersimpan apa pun), `ListPasienGawatDarurat` berisi tabel demo hardcoded, dan `DaftarGawatDaruratObstetriGinekologi` (IGD Obgyn) juga hanya landing. Iterasi 1 menjadikan alur IGD **berfungsi end-to-end** tanpa sub_menu baru (sub 6, 7, 14 sudah ada & terdaftar auto-route):

- **Registrasi IGD tersimpan**: `DaftarGawatDaruratController::store` (mirror `DaftarMedicalCheckupController::store` / `DaftarRajalController::store`), `jenis_rawat=IGD`, dengan triase, dokter jaga, zona, cara masuk, penjamin & data pengantar.
- **Pasien Darurat (Mr. X)**: pasien tanpa identitas lengkap bisa didaftarkan cepat (radical change kecil).
- **List Pasien IGD nyata**: `ListPasienGawatDaruratController::index`-query JOIN keras (pola `ListPasienRajalController`), filter tanggal/zona/triase/dokter, status dari `check_out` + `bill_temp.status_selesai`.
- **IGD Obgyn ikut aktif**: `DaftarGawatDaruratObstetriGinekologiController::store` dengan key triase/indikasi/hubungan obgyn, bagian IRD OBGYN.
- **EMR IGD sudah siap tanpa kode baru**: seluruh form baseline ber-flag `form.igd=1` (Catatan Awal Medis, SOAP/CPPT, Pengkajian, Order Resep/Lab/Rad), dan `EmrDashboardController` mensupport `jenis_rawat=IGD` → dashboard pasien IGD berfungsi via `route('dashboard_pasien.index', [registrasi_detail_id])`.

Alur data satu kunjungan IGD:

```
DaftarGawatDarurat.store  →  registrasi (jenis_rawat=IGD, tgl_masuk=waktu kedatangan, memo=pengantar JSON)
  ├─ registrasi_detail (bagian=ruang IGD ref 3, check_in, triase, cara_masuk, lokasi_rawat=zona, ket_catatan=kondisi)
  ├─ bill_temp          (nasabah, status_selesai=0)
  ├─ penanggung_rawat   (rawat_user_id = dokter jaga)
  └─ (opsional) pasien  (pasien darurat "Mr. X" bila tidak terdaftar)
```

---

## 2. Tujuan & Manfaat

| Tujuan | Manfaat |
|---|---|
| Registrasi IGD tersimpan | Pasien darurat langsung tercatat; mengalir ke list pasien & billing |
| Triase tersimpan & tampil | Prioritas penanganan (Merah/Kuning/Hijau/Hitam) terlihat di list IGD |
| Dokter jaga tersimpan (`penanggung_rawat`) | Filter/list menampilkan DPJP IGD, konsisten dengan RJ |
| Pasien darurat tanpa identitas | Kasus "Mr. X" (KLL, tidak sadar) tetap bisa dilayani, identitas dilengkapi belakangan |
| EMR IGD langsung jalan | Form `igd=1` + dashboard pasien IGD — dokter jaga isi Catatan Awal/SOAP/order tanpa kode baru |
| IGD Obgyn ikut aktif | Alur persalinan/PONED dengan triase & indikasi spesifik |

---

## 3. Struktur Modul & Sidebar (tidak berubah — hanya isi halaman)

```
Registrasi               (modul 1)
└── Pendaftaran          (menu 2)
     ├── Registrasi IGD                  (sub 6)   → menjadi berfungsi
     │    file_sub_menu = Registrasi/Pendaftaran/DaftarGawatDarurat/daftar_gawat_darurat
     └── Registrasi IGD Obgyn            (sub 7)   → menjadi berfungsi
          file_sub_menu = Registrasi/Pendaftaran/DaftarGawatDaruratObstetriGinekologi/daftar_gawat_darurat_obstetri_ginekologi

Gawat Darurat            (modul 4)
└── Pasien               (menu 5)
     └── List Pasien IGD                 (sub 14)  → menjadi berfungsi
          file_sub_menu = GawatDarurat/Pasien/ListPasienGawatDarurat/list_pasien_gawat_darurat
```

Controller & views sudah ada; yang ditambah hanya method + perbaikan view:

- `App\Http\Controllers\Registrasi\Pendaftaran\DaftarGawatDarurat\DaftarGawatDaruratController` — `index() + store()`.
- `App\Http\Controllers\Registrasi\Pendaftaran\DaftarGawatDaruratObstetriGinekologi\DaftarGawatDaruratObstetriGinekologiController` — `index() + store()`.
- `App\Http\Controllers\GawatDarurat\Pasien\ListPasienGawatDarurat\ListPasienGawatDaruratController` — `index()` (query nyata).
- Views: `daftar_gawat_darurat.blade.php`, `daftar_gawat_darurat_obstetri_ginekologi.blade.php`, `list_pasien_gawat_darurat.blade.php` (semua di path existing).

---

## 4. Konstanta `.env` — `REF_BAGIAN_IGD`

Tambah di `.env` **dan** `.env.example`:

```ini
REF_BAGIAN_IGD=3
```

- `referensi_bagian=3` = IGD → bagian aktif: 29 "Instalasi Gawat Darurat" & 30 "IRD OBGYN".
- Dipakai untuk dropdown ruang IGD (Daftar IGD) dan filter list pasien IGD (bagian ref 3).
- Setelah edit `.env` wajib recreate container: `docker compose up -d --force-recreate app vite`.
- `JENIS_RAWAT_IGD=IGD` sudah ada.

---

## 5. Migration & Model

### 5.1 Migration `add_igd_columns_to_registrasi_detail_table`

| kolom | tipe | keterangan |
|---|---|---|
| `triase` | varchar(50) nullable | Merah / Kuning / Hijau / Hitam (untuk IGD umum & Obgyn) |
| `cara_masuk` | varchar(50) nullable | Berjalan Sendiri / Kursi Roda / Brankar |

> Zona IGD memakai kolom existing `lokasi_rawat` (varchar). Waktu kedatangan = `registrasi.tgl_masuk`. Kondisi/keluhan = `registrasi_detail.ket_catatan` (varchar(1000)). Data pengantar (nama/hubungan/nohp) = `registrasi.memo` (text, JSON). Tidak butuh tabel baru.

### 5.2 Model `RegistrasiDetail`

Tambahkan `triase` & `cara_masuk` ke `$fillable` (bila `$fillable` membatasi kolom).

---

## 6. SelectOption — key baru & perbaikan key view

### 6.1 Key baru `ruang_igd` (zona IGD)

```php
'ruang_igd' => [
    ['value' => 'Ruang Resusitasi', 'label' => 'Ruang Resusitasi'],
    ['value' => 'Ruang Tindakan Non-Bedah', 'label' => 'Ruang Tindakan Non-Bedah'],
    ['value' => 'Ruang Observasi', 'label' => 'Ruang Observasi'],
    ['value' => 'Ruang Isolasi', 'label' => 'Ruang Isolasi'],
    ['value' => 'IGD Anak', 'label' => 'IGD Anak'],
],
```

### 6.2 Perbaikan key yang salah di view existing

| Di view (sekarang) | Pakai (benar) |
|---|---|
| `SelectOption::render('cara_masuk_igd')` | `SelectOption::render('cara_masuk')` (key sudah ada) |
| `SelectOption::render('dokter_igd')` | `x-select_dokter` (dokter profesi 1 aktif) |
| `SelectOption::render('jaminan')` | dropdown **nasabah** aktif (fallback "Umum / Mandiri") |
| `SelectOption::render('ruang_igd')` | `SelectOption::render('ruang_igd')` (key baru §6.1) |
| `SelectOption::render('dokter_jaga_igd')` | dropdown dokter dari tabel (dikirim controller) |

---

## 7. Registrasi IGD — `DaftarGawatDaruratController`

Meniru `DaftarMedicalCheckupController::store` (transaksi, reuse tabel). `index()` mengirim: `$ruangIgd` (bagian ref `REF_BAGIAN_IGD` aktif), `$dokters` (pegawai profesi 1 aktif), `$nasabahs` (nasabah aktif), `$zonas` (`SelectOption::get('ruang_igd')`).

### 7.1 Mode pasien

- **Pasien terdaftar** — `x-select_pasien` (preload, pola existing).
- **Pasien Darurat (Mr. X)** — radio/checkbox; saat aktif, `pasien_id` tidak wajib dan dibuat record pasien minimal:
  - `nama_pasien = 'Pasien Darurat (Mr. X)'` (bisa diedit belakangan), `no_mr` via `GenerateHelper::generateNoMr()`, `jenis_kelamin`/`tgl_lahir`/alamat null (identitas diisi belakangan di data pasien).

### 7.2 `store()` — satu transaksi

Validasi: `tipe_pasien` (terdaftar/darurat), `pasien_id` nullable (wajib bila terdaftar), `waktu_kedatangan` (required, datetime), `ruang_igd` (`bagian_id` ruang ref `REF_BAGIAN_IGD`, required), `zona` (opsional), `triase` (required, in Merah/Kuning/Hijau/Hitam), `cara_masuk` (required, in cara_masuk), `dokter_igd` (pegawai profesi 1, required), `nasabah_id` (opsional → fallback), pengantar opsional, `kondisi_tiba` (required).

Insert:
1. `Pasien` — buat bila darurat; ambil bila terdaftar.
2. `PasienNasabah` find-or-create (nasabah terpilih / "Umum / Mandiri").
3. `Registrasi`: `tgl_masuk = waktu_kedatangan`, **`jenis_rawat = env('JENIS_RAWAT_IGD','IGD')`**, `prioritas = triase`, `pasien_nasabah_id`, `memo = json_encode({nama_pengantar, hubungan_pengantar, nohp_pengantar})`.
4. `RegistrasiDetail`: `bagian_id = ruang IGD`, `tgl_daftar`, `check_in = tgl_masuk`, **`triase`**, **`cara_masuk`**, `lokasi_rawat = zona`, `ket_catatan = kondisi_tiba`, `terima_dari = 'LUAR'` (IGD sebagai pintu masuk).
5. `BillTemp`: `pasien_id/registrasi_detail_id/bagian_id/nasabah_id/tgl_bill`, `status_selesai = 0`, `bill_temp_jenis = 'IGD'`.
6. `PenanggungRawat`: `rawat_user_id = dokter_igd`, `kirim_user_id = auth()->id()`.

Flash sukses → redirect `list_pasien_gawat_darurat.index`.

### 7.3 View `daftar_gawat_darurat.blade.php`

- `action` → `route('daftar_gawat_darurat.store')` (auto dari sub 6, method `store` ada).
- Section 1: tambah radio **Pasien Terdaftar / Pasien Darurat (Mr. X)** (JS toggles `x-select_pasien` vs input peringatan).
- Section 2: dropdown **Ruang IGD** ($ruangIgd), **Zona IGD** ($zonas), **Triase** (`SelectOption::render('triase_igd')`), **Cara Masuk** (`cara_masuk`), **Dokter Jaga** (`x-select_dokter`).
- Section 3: **Penjamin** (dropdown nasabah aktif, default "Umum / Mandiri" bila kosong), pengantar input, kondisi tiba.

---

## 8. IGD Obgyn — `DaftarGawatDaruratObstetriGinekologiController`

Pola sama persis dengan §7, perbedaan:

| Aspek | IGD umum | IGD Obgyn |
|---|---|---|
| `bagian_id` | ruang IGD ref 3 (29/…) | **IRD OBGYN** (bagian 30) |
| Triase | `SelectOption::render('triase_igd')` | `SelectOption::render('triase_igd_obgyn')` |
| Field tambahan | — | **Indikasi Obgyn** (`indikasi_igd_obgyn`), Hubungan pengantar `hubungan_penanggung_obgyn` |
| View | `daftar_gawat_darurat` | `daftar_gawat_darurat_obstetri_ginekologi` |

Simpan kolom `triase` yang sama; `indikasi` bisa disimpan di `registrasi_detail.ket_catatan` (digabung dengan kondisi) atau kolom baru — **keputusan implementasi:** simpan `indikasi` ke `lokasi_rawat`? Tidak — ikut simpan di `ket_catatan` (teks) demi tanpa kolom tambahan, atau tambahkan kolom `indikasi` bila dianggap penting (diputuskan saat implementasi).

---

## 9. List Pasien IGD — `ListPasienGawatDaruratController`

### 9.1 `index()` — query JOIN keras (mirror `ListPasienRajalController`)

```php
DB::table('registrasi as r')
    ->join('registrasi_detail as rd', 'rd.registrasi_id','=','r.registrasi_id')
    ->join('pasien as p', 'p.pasien_id','=','r.pasien_id')
    ->join('bagian as b', 'b.bagian_id','=','rd.bagian_id')
    ->join('pasien_nasabah as pn', 'pn.pasien_nasabah_id','=','r.pasien_nasabah_id')
    ->join('nasabah as n', 'n.nasabah_id','=','pn.nasabah_id')
    ->join('bill_temp as bt', 'bt.registrasi_detail_id','=','rd.registrasi_detail_id')
    ->leftJoin('penanggung_rawat as pr','pr.registrasi_id','=','r.registrasi_id')
    ->where('r.jenis_rawat', env('JENIS_RAWAT_IGD','IGD'))
    ->where('b.referensi_bagian_id', env('REF_BAGIAN_IGD', 3))
    // + filter status_batal != 1 OR NULL di semua tabel (jangan whereNull saja)
```

Filter (default hari ini):
- `tanggal` (`whereDate('r.tgl_masuk', …)`).
- `ruangan` — bagian IGD (29/30) optional → `rd.bagian_id`.
- `zona` — `rd.lokasi_rawat` (dari `SelectOption::get('ruang_igd')`).
- `triase` — `rd.triase`.
- `dokter` — `pr.rawat_user_id` (dropdown dokter profesi 1 dari tabel).

Kolom baris: Tgl/waktu masuk (`r.tgl_masuk`), No. RM + nama + umur pasien, **badge triase** (warna dari class key `triase_igd`), Ruang/Zona (`b.nama_bagian` + `rd.lokasi_rawat`), Dokter Jaga (`pr.rawat_user_id` → nama pegawai), Penjamin (`n.nama_nasabah`), **Status**, Aksi.

### 9.2 Status & aksi

- **Status** (diturunkan, bukan kolom): `rd.check_out IS NULL` → **Masih Dirawat** (badge merah/kuning); `bt.status_selesai = 1` → **Selesai & Pulang** (badge hijau). Opsional: triase Merah + masih dirawat → "Tindakan Kritis".
- **Aksi "Penanganan"** → `route('dashboard_pasien.index', ['registrasi_detail' => $rd->registrasi_detail_id])` (EMR IGD — form `igd=1`).
- **Aksi "Rekam Medis"** → halaman data/riwayat pasien (link `daftar_pasien`/pasien detail, pola existing).

### 9.3 View `list_pasien_gawat_darurat.blade.php`

- Hapus 3 baris demo hardcoded → `@foreach` `$listPasien` (paginate, `components.pagination`).
- Filter: tanggal, ruangan ($ruangIgd), zona (`ruang_igd`), triase (`triase_igd`), dokter ($dokters) — semua dikirim controller.

---

## 10. Perubahan pada Fitur Existing

1. **`SelectOption`** — tambah key `ruang_igd` (§6.1). Key `cara_masuk` **sudah ada** — hapus pemakaian yang salah di view (`cara_masuk_igd`, `dokter_igd`, `jaminan`, `dokter_jaga_igd`).
2. **`DaftarRajalController`** — tidak berubah (IGD terpisah via `jenis_rawat`). Ruang IGD ref 3 tidak tampil di dropdown poli RJ (ref 1).
3. **`ListPelayananPasien`** — sudah punya opsi IGD (`JENIS_RAWAT_IGD`); tidak perlu ubah.
4. **EMR / `EmrDashboardController`** — tidak perlu ubah (flag `igd=1` + jenis_rawat sudah didukung).

---

## 11. Seeding — Ringkasan Perubahan

| Seeder / file | Perubahan |
|---|---|
| `SelectOption.php` | tambah key `ruang_igd` |
| `ModulMenuSubMenuSeeder` | **tidak ada perubahan** (sub 6, 7, 14 sudah ada & terdaftar) |
| `.env` / `.env.example` | tambah `REF_BAGIAN_IGD=3` |
| Migration | `add_igd_columns_to_registrasi_detail_table` (`triase`, `cara_masuk`) |

> Tidak perlu `db:seed` baru untuk modul ini; `migrate:fresh --seed` + `route:clear`/`cache:clear` cukup. Data demo (registrasi IGD) dibuat via smoke test, bukan seeder.

---

## 12. Route

Auto-router (`SubMenuRouteServiceProvider`) menurunkan dari `sub_menu` existing — **tanpa route manual**:

- `daftar_gawat_darurat.index/store` (sub 6 — store otomatis terdaftar karena method `store` ada).
- `daftar_gawat_darurat_obstetri_ginekologi.index/store` (sub 7).
- `list_pasien_gawat_darurat.index` (sub 14).

`dashboard_pasien/{registrasi_detail_id}` (`dashboard_pasien.index`) sudah ada manual di `routes/web.php`.

---

## 13. Risiko, Konsekuensi & Keputusan yang Harus Disepakati

1. **Pasien Darurat "Mr. X"** — pasien tanpa identitas lengkap. Risiko: duplikasi bila identitas belakangan terisi. Keputusan: catat `no_mr` baru; petugas wajib melengkapi data pasien di modul Pasien pada iterasi berikutnya. Tidak ada penanda "darurat" di kolom pasien — cukup `nama_pasien` diawali "Pasien Darurat".
2. **Triase dipindah `registrasi_detail`** — skema baru; data RI/RJ lama tanpa triase tetap valid (nullable). `registrasi.prioritas` diisi `triase` pula agar konsisten dengan pola RJ.
3. **Zona statis** di `SelectOption` — tidak ada master ruangan zona di DB; cukup enumerasi untuk filter & label. Bila nanti butuh CRUD zona, migrasi ke tabel baru (keputusan di iterasi lanjutan).
4. **Dokter jaga = `penanggung_rawat.rawat_user_id`** — konsisten dengan RJ; bila user login bukan dokter, `rawat_user_id` diisi dokter jaga terpilih (dropdown) bukan user login.
5. **Status bukan kolom** — diturunkan `check_out`/`status_selesai`; tidak ada kolom status baru di `registrasi`.
6. **IGD → Ranap** — saat admisi Ranap dari IGD, set `check_out` detail IGD (integrasi Iterasi Rawat Inap/GD, koordinasi saat implementasi).
7. **sub_menu_id 6/7/14 sudah terpakai** — tidak ada id baru; aman.
8. **EMR IGD** — form baseline `igd=1` semua; tidak perlu edit `form`/`objek`.

---

## 14. Rencana Implementasi Bertahap

### Iterasi 1 (inti — IGD umum + Obgyn + list + pasien darurat)
1. `.env` + `.env.example`: `REF_BAGIAN_IGD=3`; recreate container.
2. Migration `add_igd_columns_to_registrasi_detail_table` + update Model `RegistrasiDetail`.
3. `SelectOption`: tambah key `ruang_igd`.
4. `DaftarGawatDaruratController::store` + perbaikan view `daftar_gawat_darurat.blade.php` (mode pasien terdaftar/darurat, dropdown ruang+zona+triase+cara masuk+dokter+penjamin).
5. `DaftarGawatDaruratObstetriGinekologiController::store` + perbaikan view obgyn.
6. `ListPasienGawatDaruratController::index` + view nyata (filter + status + badge triase + aksi EMR).
7. `migrate:fresh --seed` + `route:clear`/`cache:clear` + `route:list` + smoke test HTTP + pint PASS.

### Iterasi 2 (penguatan)
- Penyelesaian pasien darurat (lengkapi identitas, merge pasien duplikat).
- Alur IGD → Ranap otomatis (`check_out` IGD saat admisi ranap) & bed IGD (obs beds di Bed Management).
- Status klinis pasien IGD dari EMR (badge "Tindakan Kritis" dari triase + pengkajian).
- Cetak kartu/registrasi IGD & rujukan keluar.

---

## 15. Referensi Kode Existing yang Dipakai

- `App\Http\Controllers\Registrasi\Pendaftaran\DaftarMedicalCheckup\DaftarMedicalCheckupController` — blueprint store pendaftaran (transaksi registrasi+detail+bill_temp+penanggung).
- `App\Http\Controllers\RawatJalan\Pasien\ListPasienRajal\ListPasienRajalController` — blueprint list pasien (JOIN keras, filter `jenis_rawat`, `emr_forms`).
- `App\Helpers\SelectOption` — key existing `triase_igd`, `triase_igd_obgyn`, `cara_masuk`, `hubungan_penanggung`, `indikasi_igd_obgyn`, `hubungan_penanggung_obgyn`; tambah `ruang_igd`.
- `components/x-select_pasien`, `x-select_dokter`, `x-validation-error`, `components.pagination`.
- `App\Models\{Registrasi,RegistrasiDetail,BillTemp,PenanggungRawat,Pasien,PasienNasabah,Nasabah}` — reuse.
- `App\Helpers\GenerateHelper` — `generateNoMr()` untuk pasien darurat.
- `App\Http\Controllers\EMR\EmrDashboard\EmrDashboardController` — dashboard pasien IGD (sudah support `jenis_rawat=IGD`); route existing `dashboard_pasien.index`.
- `routes/web.php` — route manual `dashboard_pasien` (tidak diubah).