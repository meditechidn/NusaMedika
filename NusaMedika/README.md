# NusaMedika

Aplikasi **Sistem Informasi Manajemen Rumah Sakit (SIMRS)** berbasis web untuk mendukung pelayanan rumah sakit di Indonesia, mencakup manajemen pasien, pendaftaran rawat jalan / rawat inap / IGD, EMR (Electronic Medical Record) dinamis, serta master data (bagian, pegawai, user, wilayah, kelas, jadwal dokter, ICD, dan lain-lain).

Dibangun dengan **Laravel 13 (PHP 8.3)**, **PostgreSQL**, dan **Vite + Tailwind CSS 4** untuk aset frontend.

Dokumen ini adalah panduan lengkap: arsitektur, struktur proyek, cara kerja, library yang dipakai, daftar command, cara instalasi (Docker maupun manual), serta konfigurasi environment.

---

## Daftar Isi

1. [Teknologi dan Library](#1-teknologi-dan-library)
2. [Struktur Proyek](#2-struktur-proyek)
3. [Arsitektur dan Cara Kerja](#3-arsitektur-dan-cara-kerja)
4. [Basis Data](#4-basis-data)
5. [Seeder](#5-seeder)
6. [Command-command](#6-command-command)
7. [Instalasi dengan Docker](#7-instalasi-dengan-docker)
8. [Instalasi Manual](#8-instalasi-manual-tanpa-docker) — panduan lengkap: [`deploy/README-MANUAL.md`](deploy/README-MANUAL.md)
9. [Konfigurasi Environment](#9-konfigurasi-environment)
10. [Troubleshooting](#10-troubleshooting)

> Dokumentasi deployment tambahan:
> - [`deploy/README.md`](deploy/README.md) — ringkasan stack Docker (Apache + PHP-FPM + Redis opsional)
> - [`deploy/README-MANUAL.md`](deploy/README-MANUAL.md) — instalasi manual di host (tanpa Docker)

---

## 1. Teknologi dan Library

### Backend (PHP / Composer)

| Package | Keterangan |
| :--- | :--- |
| `laravel/framework` `^13.8` | Kerangka kerja utama (routing, ORM Eloquent, Blade, Queue, Session, dll). |
| `laravel/tinker` `^3.0` | REPL interaktif (`php artisan tinker`) untuk eksperimen kode terhadap aplikasi dan database. |
| `fakerphp/faker` `^1.23` (dev) | Generator data dummy untuk pengujian / seeder. |
| `kitloong/laravel-migrations-generator` `^7.4` (dev) | Dulu dipakai untuk menghasilkan migrasi dari dump SQL legacy. Saat ini migrasi bersih sudah ditulis manual per tabel; package tetap terpasang (opsional). |
| `laravel/pail` `^1.2.5` (dev) | Menampilkan log aplikasi secara real-time di terminal (`php artisan pail`). |
| `laravel/pao` `^1.0.6` (dev) | Tooling tambahan Laravel untuk output yang ramah tooling pada pengujian PHP. |
| `laravel/pint` `^1.27` (dev) | Formatter / code style PHP (preset `laravel`, lihat `pint.json`). |
| `mockery/mockery` `^1.6` (dev) | Mocking library untuk unit test. |
| `nunomaduro/collision` `^8.6` (dev) | Error handler PHP yang lebih ramah untuk development. |
| `phpunit/phpunit` `^12.5.12` (dev) | Framework testing bawaan Laravel (`php artisan test`). |

### Frontend (Node / npm)

| Package | Keterangan |
| :--- | :--- |
| `vite` `^8.0.0` | Dev server & bundler untuk aset frontend (CSS/JS). |
| `laravel-vite-plugin` `^3.1` | Integrasi Vite dengan Laravel (direktif `@vite` di blade). |
| `tailwindcss` `^4.0.0` + `@tailwindcss/vite` | Framework CSS utility-first (digenerate lewat plugin Vite, tanpa file `tailwind.config.js`). |
| `jquery` `^3.7.1` | Dipakai sebagai dasar inisialisasi Select2 dan handler DOM. |
| `select2` `^4.0.13` | Komponen dropdown pencarian (pasien, ICD, nasabah, wilayah cascade, dll). |
| `@fortawesome/fontawesome-free` `^7.2.0` | Ikon sidebar/modul (`fa-solid ...`). |
| `concurrently` `^9.0.1` | Menjalankan beberapa proses sekaligus (dipakai script `composer dev`). |
| Font `bunny("Instrument Sans")` | Font default yang diunduh via plugin fonts `laravel-vite-plugin/fonts` (didefinisikan di `vite.config.js`). |

### Infrastruktur

- **Apache 2.4** sebagai web server (container `web`) — `mod_rewrite` + `mod_proxy_fcgi`, DocumentRoot `public/`, akses <http://localhost:8081>.
- **PHP 8.3 FPM** (container `app`) dengan ekstensi: `pdo_pgsql`, `pgsql`, `mbstring`, `exif`, `pcntl`, `bcmath`, `gd`, `zip`, `intl`, `opcache`, `redis`, `curl`, `git`, `unzip`, `postgresql-client`, `tzdata`.
- **Redis** (container `redis`) — **opsional** untuk cache / session / queue (profile `redis`). Tanpanya aplikasi tetap jalan dengan driver `file`/`sync`.
- **PostgreSQL** sebagai database — server PostgreSQL berjalan **di luar Docker** (di host ini); konfigurasi hanya di `.env` (`DB_*`). Setup sekali jalan: `sudo bash deploy/setup-postgres.sh`.
- **Docker + Docker Compose** untuk seluruh lingkungan (service: `web`, `app`, `redis`, `queue`, `vite` — lihat bagian Instalasi Docker).

---

## 2. Struktur Proyek

```
NusaMedika/
├── app/
│   ├── Console/Commands/
│   │   ├── SyncMasterMenuSeeder.php      # Command custom: seeder:sync-master-menu
│   │   └── MakeSubmenu.php               # Command custom: make:submenu (scaffold controller + view)
│   ├── Helpers/
│   │   ├── GenerateHelper.php            # GenerateHelper: no_mr, urutan antrian, estimasi, reset sequence
│   │   └── SelectOption.php              # SelectOption: daftar opsi dropdown tanpa tabel DB
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                     # AuthController (login/logout, password plaintext)
│   │   │   ├── Dashboard/                # DashboardController
│   │   │   ├── EMR/                      # EmrDashboard, DynamicFormController, Soap, PengkajianAwalKeperawatan
│   │   │   ├── Registrasi/               # Pasien, Pendaftaran (DaftarRajal, DaftarRanap, DaftarGawatDarurat, ...)
│   │   │   ├── RawatJalan/               # ListPasienRajal, ListPasienDokter
│   │   │   ├── RawatInap/               # ListPasienRanap
│   │   │   ├── GawatDarurat/             # ListPasienGawatDarurat
│   │   │   ├── Administrator/            # ManajemenMaster (Modul, Menu, SubMenu, Bagian, Pegawai, User, Wilayah, ...)
│   │   │   ├── API/                      # ApiPasien, ApiWilayah, ApiIcd, ApiNasabah, ApiPegawai
│   │   │   ├── SubMenu/                  # SubMenuViewController (view statis)
│   │   │   └── Controller.php            # Base controller
│   │   └── View/Composers/
│   │       ├── SidebarComposer.php       # Bangun sidebar per user (cache 24 jam)
│   │       └── InformasiPasienComposer.php # Pasang informasi pasien di halaman EMR
│   ├── Models/                           # 28 model Eloquent (lihat bagian Basis Data)
│   ├── Providers/
│   │   ├── AppServiceProvider.php        # Daftarkan view composer
│   │   └── SubMenuRouteServiceProvider.php # Auto-route dari tabel sub_menu
│   └── View/Components/
│       ├── SelectPoliklinik.php          # x-select_poliklinik
│       ├── SelectDokter.php              # x-select_dokter
│       └── SelectRuangPerawatan.php      # x-select_ruang_perawatan
├── bootstrap/
│   └── providers.php                     # Daftar provider aplikasi
├── config/                               # Konfigurasi default Laravel
├── database/
│   ├── migrations/                       # 40 file migrasi (38 tabel + view header_ehr + 1 rename)
│   └── seeders/                          # DatabaseSeeder + seeder per master data
├── resources/
│   ├── css/app.css                       # Style global (Tailwind)
│   ├── js/
│   │   ├── app.js                        # entrypoint: import select.js + drag-scroll handler
│   │   └── select.js                     # semua inisialisasi Select2 (pasien, ICD, ajax, wilayah cascade)
│   └── views/
│       ├── layouts/                      # app.blade.php, sidebar, navbar, footer, iframe
│       ├── components/                   # komponen blade reusable (x-select_*, informasi-pasien, emr-*, dll)
│       ├── auth/                         # halaman login
│       ├── dashboard/                    # halaman dashboard
│       └── moduls/
│           ├── {Modul}/{Menu}/{SubMenu}/{basename}*.blade.php  # halaman per sub-menu
│           └── EMR/                      # EmrDashboard, Soap, PartialForm, dsb.
├── routes/web.php                        # Route manual (auth, dashboard, EMR, API). Route CRUD lain otomatis.
├── docker-compose.yml                    # Service web (Apache :8081) + app (PHP-FPM) + redis (opsional) + queue + vite (:5173)
├── Dockerfile                            # Image php:8.3-fpm multi-target: `fpm` & `apache` (proxy_fcgi)
├── deploy/                               # Konfigurasi container: vhost Apache, tuning PHP-FPM, redis.conf, panduan
├── vite.config.js                        # Konfigurasi Vite (input css/js, tailwind, font, port 5173)
├── package.json                          # Dependensi npm & script build/dev
├── composer.json                         # Dependensi composer & script (dev, test, lint, format)
├── pint.json                             # Preset Pint: laravel
└── AGENTS.md                             # Dokumen konvensi pengembangan (baca sebelum coding)
```

### Konvensi folder modullar (penting)

Setiap sub-menu di sidebar mengikuti pola folder yang sama persis antara controller dan view:

- Controller: `app/Http/Controllers/{Modul}/{Menu}/{SubMenu}/{SubMenu}Controller.php`
- View: `resources/views/moduls/{Modul}/{Menu}/{SubMenu}/{basename}[_create|_edit|_form].blade.php`

Contoh untuk sub-menu "Daftar Pasien":

- `Registrasi/Pasien/DaftarPasien/DaftarPasienController.php`
- `resources/views/moduls/Registrasi/Pasien/DaftarPasien/daftar_pasien.blade.php`

Aturan yang berlaku:

- Folder leaf (nama sub-menu) harus **PascalCase kata utuh tanpa akronim** (`DaftarGawatDarurat`, `ListPasienRanap`, `NasabahPasien`).
- Nama file blade = basename folder leaf dalam snake_case. URI dan nama route **identik dengan nama file blade tersebut** (misal URL `/daftar_pasien` = file `daftar_pasien.blade.php`).
- Basename **wajib unik di seluruh aplikasi** karena menjadi URI/route (dua sub-menu dengan basename sama akan bentrok).
- Sub-folder partial EMR berada di `moduls/EMR/PartialForm/`.

Untuk membuat sub-menu baru, gunakan scaffold `make:submenu` (lihat §6.2) — membangkitkan controller + view (index/create/edit) kosong berkomentar dengan `<x-page-header>`, lalu isi logika & daftarkan record `sub_menu` dengan `file_sub_menu='{Modul}/{Menu}/{SubMenu}/{basename}'`.

---

## 3. Arsitektur dan Cara Kerja

### 3.1 Auto-route dari tabel `sub_menu`

Semua route CRUD modul non-EMR (Registrasi, Rawat Jalan/Inap/GD, Administrator) **tidak ditulis manual** di `routes/web.php`. Route dibangkitkan otomatis saat aplikasi boot oleh `SubMenuRouteServiceProvider` (`bootstrap/providers.php`):

1. Saat aplikasi `booted()`, provider membaca semua baris `sub_menu` aktif (`status_batal != 1`).
2. Setiap `file_sub_menu` diturunkan (`derive()`) menjadi:
   - `uri` = basename (segmen terakhir path, misal `daftar_pasien`).
   - `controller` = `App\Http\Controllers\{folder}\{folderLeaf}Controller` (folder = segmen sebelum basename).
   - `route_name` = `admin.{uri}` bila folder berawal `Administrator`, selain itu `{uri}` dan di-suffix method (`.index`, `.create`, `.store`, `.edit`, `.update`, `.destroy`).
3. Route yang didaftarkan **hanya method yang benar-benar ada** di controller (`method_exists`), semuanya dengan middleware `web` + `auth`.
4. Bila controller tidak ada (view statis/landing), didaftarkan satu route GET `/{uri}` ke `SubMenuViewController` bila view `moduls.{folder}.{basename}` ada.
5. Provider **menolak** URI yang sudah terdaftar (`$existing`), sehingga route manual di `routes/web.php` selalu menang atas route otomatis yang bentrok.

Route manual yang tersisa di `routes/web.php` adalah: login/logout, dashboard, `dashboard_pasien.index`, `emr.dynamic.index`, CRUD SOAP dan Pengkajian Awal Keperawatan, serta semua route API (`/api/*`).

URL sidebar dihitung lewat `SubMenuRouteServiceProvider::url($file_sub_menu)` (`resources/views/layouts/sidebar.blade.php`).

### 3.2 Sidebar per user

`SidebarComposer` (didaftarkan di `AppServiceProvider`) membangun menu `modul -> menu -> sub_menu` hanya untuk sub_menu yang dimiliki user (tabel `user_akses`), lalu **di-cache 24 jam** per user dengan key `sidebar_moduls_user_{user_id}`.

Konsekuensi: setelah mengubah `modul`/`menu`/`sub_menu`/`user_akses`, cache sidebar harus dihapus (`logout` otomatis melakukan `Cache::forget`, atau hapus manual di controller). `ModulController`/`MenuController`/`SubMenuController` juga meng-clear cache sidebar semua user saat terjadi perubahan.

### 3.3 Autentikasi (password plain text)

- `AuthController::login` membandingkan `user_password` secara langsung (tidak pakai bcrypt).
- Kolom `users.user_password` adalah `varchar(30)`.
- Field login: `user_name` + `user_password`.
- Jangan gunakan `/default UserFactory` (ditujukan ke kolom `name`/`email`/`password` yang tidak ada).

### 3.4 EMR dinamis

- Route `/emr/form/{form_name}/{registrasi_detail_id}/{emr_id?}` (`emr.dynamic.index`) dipakai untuk semua form EMR. `DynamicFormController` mencarikan controller `App\Http\Controllers\EMR\{Studly}\{Studly}Controller`; bila tidak ada, menampilkan view `moduls.EMR.{slug}.index`; bila view juga belum ada, menampilkan halaman "under construction".
- Dashboard pasien (`/dashboard_pasien/{registrasi_detail_id}`) dan menu EMR diambil dari view `header_ehr` (dibangun dari `dashboard_menu`/`dashboard_menu_sub`/`dashboard_menu_sub_extra`) yang di-join ke `form.id_dash_menu` dan `akses_ehr.profesi_id`. `profesi_id` aktif diambil dari session.
- **ID form/objek dibaca runtime dari `.env`** (`env('FORM_ID_SOAP')`, `env('OBJEK_ID_SISTOLIK')`, `env('JENIS_RAWAT_RJ')`, dsb.), bukan dari `config()`. Konsekuensinya: **jangan** menjalankan `config:cache` bila konstanta SIMRS belum di-set, karena aplikasi akan kehilangan nilai tersebut.

### 3.5 Soft delete & timestamp

- **Soft delete via `status_batal`**: `null` atau `0` = aktif; `1+` = batal. Semua query GET wajib memfilter `status_batal` dengan pola `where(function ($q) { $q->whereNull('status_batal')->orWhere('status_batal', 0); })` (jangan hanya `whereNull`).
- **Tidak ada timestamps Laravel**: tabel memakai `input_time` / `mod_time` (timestamp) + `input_user_id` / `mod_user_id`. Semua model men-set `public $timestamps = false`.
- **Primary key auto-increment** (`{table}_id` via `increments()` / serial Postgres). Jangan set PK manual di controller/seeder; seeder memakai `updateOrInsert` ber-ID eksplisit lalu `GenerateHelper::resetSequence()` menyesuaikan sequence.

### 3.6 Beberapa tabel tanpa Model

Sebagian tabel dipakai langsung via `DB::table()` tanpa model Eloquent, yaitu tabel EMR (`emr`, `emr_detail`, `form`, `objek`, `objek_form_control`, `dashboard_menu`, `dashboard_menu_sub`, `dashboard_menu_sub_extra`, `akses_ehr`) dan `header_ehr` (view). Tabel `bed` (dipakai filter `ListPasienRanapController`) dibuatkan migrasi meski tanpa model.

### 3.7 Helper

| Helper | Fungsi |
| :--- | :--- |
| `GenerateHelper::resetSequence($table, $pk = null)` | Menyetel ulang sequence Postgres mengikuti MAX(id)+1 (dipakai seeder). |
| `GenerateHelper::generateNoMr()` | Membuat No. MR baru: `MAX(no_mr)+1` atas SEMUA baris `pasien`, format 7 digit. |
| `GenerateHelper::generateNoUrut($pegawaiId, $bagianId, $tgl)` | Nomor urut antrian rawat jalan berikutnya per dokter+poli+tanggal. |
| `GenerateHelper::hitungEstimasi(...)` | Estimasi waktu layanan (jam mulai + rentang 60 menit x (urutan-1)). |
| `SelectOption::all()/get()/render()` | Daftar opsi dropdown yang TIDAK tersimpan di database (19 key: jenis kelamin, agama, golongan darah, triase, kategori ICD, dll). Pakai `render(key, selected, placeholder)` untuk langsung mencetak `<option>`. Poliklinik, dokter, ruang, dan kelas perawatan BUKAN di sini (diambil dari tabel). |

### 3.8 Frontend & Select2

`resources/js/select.js` menginisialisasi semua komponen dropdown:

- **`.select2-pasien`**: data pasien di-preload sekali dari API (`limit:1000`) lalu dijadikan `<option>` asli dan Select2 **statis + matcher klien** (JANGAN ganti ke modul ajax; dropdown akan kosong saat dibuka).
- **`.select2-icd` / `.select2-ajax`**: pola yang sama (preload + statis matcher).
- **`.select2`, `.select2-poliklinik`, `.select2-ruangan`, `.select2-dokter`**: Select2 statis untuk select biasa.
- **`.wilayah-cascade`**: cascade provinsi -> kabupaten -> kecamatan -> kelurahan dengan **modul ajax Select2** (`initWilayahCascade`), dilengkapi prefill dari `data-prefill`, dan otomatis mengosongkan turunan saat parent berubah.
- `pasangSelect2()` menghancurkan instance lama (`select2('destroy')`) sebelum init ulang agar opsi baru diterapkan.
- Form reset menyinkronkan semua Select2 kembali ke `null`.

Komponen blade reusable:
`x-select_poliklinik`, `x-select_dokter`, `x-select_ruang_perawatan`, `x-select_pasien`, `x-select_ajax`, `x-informasi_pasien`, `x-emr-split-layout`, `x-emr-accordion`, `x-pagination`, `x-loading`.

---

## 4. Basis Data

Database utama adalah **PostgreSQL eksternal** (lihat `DB_*` di `.env`). Hanya driver `pgsql` yang relevan.

Terdapat **39 migrasi bersih** (dibuat ulang dari nol menggantikan 295 migrasi legacy) dengan satu migrasi tambahan untuk rename tabel `referensi_bagian_id` ke `referensi_bagian`. Semua migrasi menggunakan `$table->increments(...)` (serial) untuk PK.

Ringkasan objek database:

| Kelompok | Tabel / Objek |
| :--- | :--- |
| Master & referensi | `referensi_bagian`, `bagian`, `profesi`, `jabatan`, `status_kepegawaian`, `pegawai`, `kelas_ruang`, `nasabah`, `icd`, `jadwal_dokter`, `bed` |
| Menu & akses | `modul`, `menu`, `sub_menu`, `users`, `user_akses` |
| Wilayah | `provinsi`, `kabupaten`, `kecamatan`, `kelurahan` |
| Pasien & registrasi | `pasien`, `pasien_nasabah`, `registrasi`, `registrasi_detail`, `registrasi_urut`, `rujukan_sep`, `penanggung_rawat`, `diagnosa_rawat` |
| EMR (tanpa model, via `DB::table`) | `emr`, `emr_detail`, `form`, `objek`, `objek_form_control`, `dashboard_menu`, `dashboard_menu_sub`, `dashboard_menu_sub_extra`, `akses_ehr` |
| View | `header_ehr` |

**27 model Eloquent** di `app/Models/` (`Modul`, `Menu`, `SubMenu`, `User`, `UserAkses`, `Profesi`, `Jabatan`, `StatusKepegawaian`, `Pegawai`, `Bagian`, `ReferensiBagian`, `Provinsi`, `Kabupaten`, `Kecamatan`, `Kelurahan`, `Nasabah`, `KelasRuang`, `ICD`, `JadwalDokter`, `Pasien`, `PasienNasabah`, `Registrasi`, `RegistrasiDetail`, `RegistrasiUrut`, `RujukanSep`, `PenanggungRawat`, `DiagnosaRawat`).

> **Catatan:** `migrate:fresh` akan **DROP SEMUA tabel** di database yang dikonfigurasi (termasuk tabel legacy yang tidak punya migrasi) lalu recreate hanya 39 objek di atas. Proses ini destruktif dan irreversibel. Jangan jalankan terhadap database produksi/berisi data.

### Master wilayah

Empat tabel `provinsi` / `kabupaten` / `kecamatan` / `kelurahan` tidak punya kolom audit (hanya PK, parent id, `nama_*`, `status_batal`, `kode_wilayah_*`). CRUD dilakukan dalam satu halaman `/wilayah` dengan tab dan parameter query `?tab=provinsi|kabupaten|kecamatan|kelurahan`. Soft-delete berjenjang (hapus provinsi menghapus turunannya) memakai `whereIn('col', $ids)`, bukan `where('col', $collection)`.

### Master kelas

Tabel `kelas_ruang` (Kelas 1/2/3 dengan `kelas_bpjs`, plus VIP/VVIP dengan `kelas_khusus='KHUSUS'`). Semua dropdown kelas perawatan memakai `KelasRuang::aktif()`.

### Master ICD

Tabel `icd` (kode/nama diagnosa + kategori ICD-10/ICD-9). Form menyimpan `kode_diagnosa`, `nama_diagnosa`, `kategori`; kolom `jenis_diagnosa`/`penyakit_id` di-null-kan (belum dipakai).

---

## 5. Seeder

`php artisan db:seed` memanggil `DatabaseSeeder` yang menjalankan seeder berikut **berurutan**:

| Urutan | Seeder | Isi |
| :--- | :--- | :--- |
| 1 | `ReferensiBagianSeeder` | Referensi jenis bagian (`RAWAT JALAN`, `RAWAT INAP`, `IGD`). |
| 2 | `BagianSeeder` | Menghapus record lama lalu seed ulang: 18 Poli (ref=1/RJ), 19 Ruang Perawatan (ref=2/RI), 2 IGD (ref=3/IGD). |
| 3 | `ModulMenuSubMenuSeeder` | Modul -> menu -> sub_menu. **File ini dihasilkan otomatis oleh command `seeder:sync-master-menu`** (lihat di bawah). |
| 4 | `MasterPegawaiSeeder` | `jabatan` (1-8), `status_kepegawaian` (1-4), `pegawai` (1-3: Administrator Sistem, Perawat Jaga, Dokter Jaga). |
| 5 | `UserSeeder` | `users`: superadmin/admin, perawat/perawat, dokter/dokter + `user_akses`. Admin diberi akses semua sub_menu. |
| 6 | `WilayahSeeder` | Contoh master wilayah: 3 provinsi, 12 kabupaten, 10 kecamatan, 20 kelurahan. |
| 7 | `KelasRuangSeeder` | Kelas 1/2/3 + VIP/VVIP. |
| 8 | `IcdSeeder` | Contoh data ICD-10 / ICD-9. |

Di akhir, `DatabaseSeeder` memanggil `GenerateHelper::resetSequence()` untuk 15 tabel (referensi_bagian, bagian, modul, menu, sub_menu, profesi, jabatan, status_kepegawaian, pegawai, users, provinsi, kabupaten, kecamatan, kelurahan, kelas_ruang). Ini penting agar insert berikutnya dari aplikasi (auto-increment) tidak bentrok dengan ID seeder.

Semua seeder **idempotent** (aman dijalankan ulang): modul/menu/sub_menu/users memakai `updateOrInsert` berbasis PK.

### Command `seeder:sync-master-menu`

Jika kamu membuat modul, menu, atau sub_menu baru lewat aplikasi (atau langsung di DB), file seeder **tidak perlu diedit manual**:

```bash
docker compose exec app php artisan seeder:sync-master-menu
```

Command ini (`App\Console\Commands\SyncMasterMenuSeeder`) membaca record **aktif** dari tabel `modul`/`menu`/`sub_menu` dan menulis ulang `database/seeders/ModulMenuSubMenuSeeder.php` (dengan `updateOrInsert` + komentar pengelompokan per modul/menu). Record yang dimark `status_batal=1` otomatis tidak ikut di-seed. Setelah itu jalankan `db:seed` bila perlu.

---

## 6. Command-command

> Stay: tidak ada PHP terpasang di host. Semua perintah artisan harus dijalankan **di dalam container app** lewat `docker compose exec app php artisan <cmd>` (kecuali instalasi manual).

### 6.1 Docker Compose

| Kebutuhan | Perintah |
| :--- | :--- |
| Build image & start semua service (tanpa Redis) | `docker compose up -d --build` |
| Start dengan Redis aktif | `docker compose --profile redis up -d --build` |
| Start ulang dengan konfigurasi `.env` terbaru | `docker compose up -d --force-recreate app web` |
| Lihat status container | `docker compose ps` |
| Status + Redis | `docker compose --profile redis ps` |
| Log Apache (web) | `docker compose logs -f web` |
| Log PHP-FPM (app) | `docker compose logs -f app` |
| Log vite | `docker compose logs -f vite` |
| Log Redis | `docker compose exec redis redis-cli monitor` |
| Masuk shell container app | `docker compose exec -it app bash` |
| Redis CLI | `docker compose exec redis redis-cli` |
| Stop container | `docker compose down` |
| Hapus container + volume | `docker compose down -v` |

### 6.2 Artisan (lewat container)

| Kebutuhan | Perintah |
| :--- | :--- |
| Cek versi | `docker compose exec app php artisan --version` |
| Jalankan migrasi (DB baru) | `docker compose exec app php artisan migrate` |
| Jalankan migrasi dari nol (DESTRUKTIF) | `docker compose exec app php artisan migrate:fresh --seed` |
| Cek status migrasi | `docker compose exec app php artisan migrate:status` |
| Jalankan semua seeder | `docker compose exec app php artisan db:seed` |
| Jalankan satu seeder | `docker compose exec app php artisan db:seed --class=ModulMenuSubMenuSeeder` |
| Sync seeder master menu (custom) | `docker compose exec app php artisan seeder:sync-master-menu` |
| Scaffold sub_menu baru (controller + view kosong) | `docker compose exec app php artisan make:submenu Administrator/ManajemenMaster/Contoh --force` |
| REPL interaktif | `docker compose exec app php artisan tinker` |
| Daftar route | `docker compose exec app php artisan route:list` |
| Cache route (wajib ulang setelah ubah sub_menu) | `docker compose exec app php artisan route:cache` |
| Bersihkan cache route | `docker compose exec app php artisan route:clear` |
| Kompilasi semua blade | `docker compose exec app php artisan view:cache` |
| Hapus cache view | `docker compose exec app php artisan view:clear` |
| Bersihkan semua cache (optimize:clear) | `docker compose exec app php artisan optimize:clear` |
| Jalankan test suite | `docker compose exec app php artisan test` |
| Tail log real-time | `docker compose exec app php artisan pail` |
| Queue worker (kalau Redis aktif) | `docker compose exec app php artisan queue:work redis` / `queue:listen` |
| Format kode (Pint) | `docker compose exec app vendor/bin/pint` |
| Cek style (Pint test-mode) | `docker compose exec app vendor/bin/pint --test` |

### 6.3 NPM (lewat container vite / di host untuk instalasi manual)

| Kebutuhan | Perintah |
| :--- | :--- |
| Dev server Vite (hot reload) | `npm run dev -- --host 0.0.0.0` |
| Build aset untuk produksi | `npm run build` |

### 6.4 Composer scripts

| Script | Isi |
| :--- | :--- |
| `composer dev` | Menjalankan sekaligus: `php artisan serve`, `queue:listen`, `pail`, dan `npm run dev` (via `concurrently`). Hanya berguna bila PHP tersedia (di dalam container / install manual). |
| `composer test` | `php artisan config:clear` lalu `php artisan test`. |
| `composer lint` | `vendor/bin/pint --test` (hanya cek). |
| `composer format` | `vendor/bin/pint` (format otomatis). |

> Note: `phpunit.xml` memaksa driver `sqlite :memory:`, sehingga kode yang bergantung pada PostgreSQL (Eloquent/DB nyata) tidak akan berjalan apa adanya di bawah test.

### 6.5 Akses dari device lain (HP / laptop lain) via IP

Aplikasi + dev server Vite bisa dibuka dari device lain di jaringan yang sama, **tanpa
perlu mengubah konfigurasi setiap kali IP komputer berubah**:

```bash
# di komputer server
docker compose up -d vite            # dev server Vite (port 5173)
ip -4 addr show | grep inet          # lihat IP LAN, mis. 10.100.1.80
```

Lalu di HP/laptop lain (device harus di Wi-Fi yang sama):

- Aplikasi: `http://10.100.1.80:8081`
- Dev server Vite: `http://10.100.1.80:5173` (port 5173 harus terbuka di firewall)

Yang membuatnya otomatis:

| Bagian | Perilaku |
| :--- | :--- |
| `vite.config.js` | Mendeteksi IP LAN komputer otomatis (route default, fallback `os.networkInterfaces()`) lalu menulis IP tersebut ke `public/hot`. Override manual: env `DEV_HOST`. |
| `vite.config.js` | `server.host = '0.0.0.0'`, `server.origin = ''` (URL aset **relatif** — kalau origin diisi, webfont Font Awesome ditulis absolut `http://0.0.0.0:5173/...` sehingga ikon sidebar hilang di device lain), `server.hmr.host` **tidak** di-set (client HMR memakai host dari URL `@vite/client`), `server.cors.origin = true` (agar module script tidak diblokir CORS), `server.allowedHosts = true` (boleh diakses lewat hostname). |
| `app/Support/Vite.php` | Override `hotAsset()`: host pada `public/hot` diganti dengan host request, jadi asset selalu diambil dari IP/host yang sedang dipakai user (localhost, IP LAN, atau hostname) — termasuk HMR websocket-nya. |

Kalau `public/hot` kosong (dev server mati), asset dilayani dari build statis:
`docker compose exec vite npm run build`.

### 6.6 Tampilan responsif (HP / tablet)

Seluruh aplikasi sudah menyesuaikan tampilan untuk layar kecil — tidak perlu
konfigurasi tambahan:

| Bagian | Perilaku |
| :--- | :--- |
| `resources/views/layouts/app.blade.php` | Sidebar jadi **drawer off-canvas** di bawah 1024px (tombol hamburger di navbar, backdrop gelap, tutup otomatis saat menu dipilih / tekan Escape). Di desktop tetap kolom tetap + bisa di-collapse jadi ikon. Konten dipadding `p-3 sm:p-4 lg:p-6`. |
| `resources/views/layouts/navbar.blade.php` | Counter "Rawat Inap/Rawat Jalan" disembunyikan teksnya di layar kecil (hanya ikon + angka), popover notifikasi & speedtest memakai lebar `w-[calc(100vw-1.5rem)]` supaya tidak keluar layar. |
| `resources/css/app.css` | Blok `RESPONSIF MOBILE (global)`: grid yang berisi form jadi **satu kolom** di bawah 640px (`:has(input, select, textarea)`), baris tombol/filter boleh membungkus, kontrol form dibatasi lebar layar, font input minimal 16px (anti auto-zoom iOS), `touch-action: manipulation` (hilangkan jeda tap). |
| `resources/js/app.js` | `bungkusTabel()` membungkus setiap tabel dengan container scroll horizontal (`.nusa-table-scroll`) sehingga tabel lebar bisa digeser di HP, bukan terpotong. |
| `resources/views/components/emr-split-layout.blade.php` | Di bawah 768px panel riwayat (45vh) berada di atas panel form, tombol maximize disembunyikan. |
| `resources/views/components/page-header.blade.php` | Judul + tombol aksi membungkus (`flex-wrap`) di layar kecil. |

Verifikasi cepat (butuh Google Chrome di host):

```bash
# cek tidak ada elemen yang melebar melebihi layar
google-chrome --headless=new --window-size=390,844 --screenshot=/tmp/m.png "http://localhost:8081/dashboard"
```

---

## 7. Instalasi dengan Docker

Stack: **Apache 2.4 + PHP 8.3 FPM (+ Redis opsional)**, database **eksternal**.

### Prasyarat

- Docker Engine (Docker Desktop di Windows/macOS, atau daemon Docker di Linux).
- Plugin **Docker Compose v2** (sudah termasuk di Docker Desktop / diinstal terpisah di Linux).
- Server **PostgreSQL** yang bisa diakses — **di luar Docker**; koneksi ditentukan oleh `.env`. Di host ini: `sudo bash deploy/setup-postgres.sh` (buat role + database `meditech` + aturan pg_hba untuk jaringan Docker).

### Langkah-langkah

1. **Clone / masuk ke direktori proyek**

   ```bash
   git clone <url-repo> NusaMedika
   cd NusaMedika
   ```

2. **Siapkan file `.env`**

   ```bash
   cp .env.example .env
   ```

   Isi blok database (server **luar** Docker):

   ```dotenv
   DB_CONNECTION=pgsql
   DB_HOST=host.docker.internal
   DB_PORT=5432
   DB_DATABASE=meditech
   DB_USERNAME=meditech
   DB_PASSWORD=meditech
   DB_SSLMODE=prefer
   ```

   > `host.docker.internal` hanya berlaku dari dalam container (compose memakai
   > `extra_hosts: host.docker.internal:host-gateway`). Untuk instalasi manual di host,
   > ganti dengan `127.0.0.1`.

   Pilih mode cache/session/queue (lihat §9):

   ```dotenv
   # tanpa redis (default)
   CACHE_STORE=file
   SESSION_DRIVER=file
   QUEUE_CONNECTION=sync

   # dengan redis
   CACHE_STORE=redis
   SESSION_DRIVER=redis
   QUEUE_CONNECTION=redis
   REDIS_HOST=redis
   ```

   Juga pastikan konstanta SIMRS (bagian `JENIS_RAWAT_*`, `FORM_ID_*`, `OBJEK_ID_*`, `PROFESI_ID_DOKTER`, `REF_BAGIAN_RAJAL`) terisi. **`.env` berisi password database dan tidak boleh di-commit ke git.**

3. **Build & jalankan container**

   ```bash
   docker compose up -d --build            # tanpa Redis
   docker compose --profile redis up -d --build   # dengan Redis
   ```

   Yang terjadi saat pertama kali:
   - Image dibangun dari `Dockerfile` multi-target: `fpm` (PHP 8.3 FPM + ekstensi + Composer) dan `apache` (Apache + `mod_proxy_fcgi`).
   - Container `app` menjalankan `composer install` lalu `php-fpm` (menjadi backend `.php`).
   - Container `web` menjalankan Apache (DocumentRoot `public/`) dan meneruskan request `.php` ke `app:9000`.
   - Container `vite` (Node 22) menjalankan `npm install` lalu `npm run dev -- --host 0.0.0.0` (dev server di port 5173).
   - Nilai `.env` diinjeksi otomatis (`env_file`) dan juga dibaca Laravel langsung dari file `.env` di volume.

4. **Siapkan schema (hanya untuk database baru)**

   Jika database masih kosong:

   ```bash
   docker compose exec app php artisan migrate --seed
   ```

   Jika kamu memakai database yang sudah berisi data, **LEWATI langkah migrasi** — jangan jalankan `migrate:fresh`.

5. **Akses aplikasi**

   Buka `http://localhost:8081` (port 80/8080 di host sudah dipakai aplikasi lain).

   Login default dari seeder:

   | User | Password |
   | :--- | :--- |
   | `superadmin` | `admin` |
   | `perawat` | `perawat` |
   | `dokter` | `dokter` |

6. **Setelah mengubah `.env`**

   Nilai `.env` terbaca saat container start. Setelah mengeditnya, wajib recreate:

   ```bash
   docker compose up -d --force-recreate app web
   ```

   Lalu bersihkan cache Laravel:

   ```bash
   docker compose exec app php artisan config:clear
   docker compose exec app php artisan cache:clear
   ```

   > **Jangan** `php artisan config:cache` — aplikasi membaca `env('FORM_ID_*')` / `env('OBJEK_ID_*')` saat runtime.

### Struktur Docker (komposisi aktual)

`docker-compose.yml` berisi **lima service**:

| Service | Image | Peran | Port |
| :--- | :--- | :--- | :--- |
| `web` | target `apache` | Apache 2.4 (`rewrite` + `proxy_fcgi`), DocumentRoot `public/` | host **8081** → container 80 |
| `app` | target `fpm` | PHP 8.3 FPM + `artisan`, `composer install` saat start | internal `app:9000` |
| `redis` | `redis:7-alpine` | cache/session/queue — **opsional** (profile `redis`) | internal `redis:6379` |
| `queue` | target `fpm` | `artisan queue:work redis` — ikut profile `redis` | — |
| `vite` | `node:22-alpine` | Vite dev server | host network, 5173 |

- **Tidak ada service `db`** — database bersifat eksternal dan dikonfigurasi hanya lewat `.env`.
- Container `app` (dan `queue`) berjalan sebagai **user host (uid 1000)** via `user: "1000:1000"`, sehingga file yang dibuat dari dalam container (cache, log, scaffold `make:submenu`) dimiliki uid 1000: bisa diedit/dihapus dari host tanpa `sudo`. Bila uid host teammate berbeda, sesuaikan nilai `user:` dengan `id -u`/`id -g` (jangan dihapus barisnya).
- Container `web` berjalan sebagai root di dalam container (agar bisa bind port 80); penulisan file aplikasi tetap terjadi di container `app` (uid 1000).
- **Redis opsional**: tanpa profile `redis`, aplikasi berjalan dengan `CACHE_STORE=file`, `SESSION_DRIVER=file`, `QUEUE_CONNECTION=sync` (tidak butuh Redis sama sekali). Driver `database` tidak tersedia untuk cache/session/queue karena tabel `cache`/`sessions`/`jobs` tidak ada di migrasi project.

> Panduan lengkap ada di `deploy/README.md`.

---

## 8. Instalasi Manual (tanpa Docker)

> Cara **alternatif** untuk environment yang tidak boleh memakai Docker. Stack yang sama:
> **Apache 2.4 + PHP-FPM (+ Redis opsional)**, database tetap **eksternal**.
>
> **Panduan lengkap ada di [`deploy/README-MANUAL.md`](deploy/README-MANUAL.md)** —
> berisi install paket, vhost Apache, pool PHP-FPM, permission `www-data`, `.env`,
> systemd queue/scheduler, aktivasi Redis, verifikasi, dan troubleshooting.

### Ringkas

```bash
# 1. Paket sistem
sudo apt-get update
sudo apt-get install -y apache2 redis-server \
  php8.5-fpm php8.5-cli php8.5-mysql php8.5-pgsql php8.5-mbstring php8.5-xml \
  php8.5-curl php8.5-zip php8.5-bcmath php8.5-intl php8.5-gd php8.5-redis

# 2. Apache + PHP-FPM
sudo a2enmod rewrite proxy proxy_fcgi headers
echo "Listen 8081" | sudo tee -a /etc/apache2/ports.conf
sudo cp deploy/apache/nusamedika-manual.conf /etc/apache2/sites-available/nusamedika.conf
sudo sed -i "s#__APP_DIR__#$PWD#g; s#__PHP_VER__#8.5#g; s#__PORT__#8081#g" \
  /etc/apache2/sites-available/nusamedika.conf
sudo cp deploy/php/php-fpm-pool-manual.conf /etc/php/8.5/fpm/pool.d/www.conf
sudo cp deploy/php/php.ini /etc/php/8.5/{fpm,cli}/conf.d/99-nusamedika.ini
sudo a2ensite nusamedika && sudo apache2ctl configtest

# 3. Dependency + aset (sebagai user biasa)
composer install
sudo chown -R $USER:$USER node_modules public/build vendor   # bila root-owned
npm install && npm run build
php artisan storage:link

# 4. Service
sudo systemctl enable --now apache2 php8.5-fpm redis-server
```

Buka `http://localhost:8081` dan login (tabel kredensial di §7).

> **Catatan:** port 8081 sama dengan stack Docker — jalankan **salah satu** saja
> (`docker compose stop web` bila memilih instalasi manual).
> Di mode manual, `REDIS_HOST` memakai `127.0.0.1` (bukan `redis` seperti di Docker).

### Development ringan di host (tanpa Apache)

```bash
php artisan serve --port=8000     # terminal 1
npm run dev -- --host 0.0.0.0     # terminal 2
```

### Instalasi manual plus production build

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
npm install
npm run build
php artisan migrate --force
php artisan db:seed --force
```

---

## 9. Konfigurasi Environment

Variabel penting selain standar Laravel (`.env.example` sudah berisi semua):

| Variabel | Contoh | Keterangan |
| :--- | :--- | :--- |
| `DB_CONNECTION` | `pgsql` | Driver database (PostgreSQL, server **di luar Docker**). |
| `DB_HOST` / `DB_PORT` | `host.docker.internal` / `5432` | Host PostgreSQL. Dari container = `host.docker.internal`; dari host = `127.0.0.1`. |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | `meditech` | Kredensial database (dibuat oleh `deploy/setup-postgres.sh`). |
| `DB_SSLMODE` | `prefer` | Mode SSL PostgreSQL (`require` untuk server cloud). |
| `APP_URL` | `http://localhost:8081` | URL aplikasi (sesuai port publish Apache). |
| `CACHE_STORE` | `file` / `redis` | Cache store. `redis` butuh container `redis` (profile `redis`). |
| `SESSION_DRIVER` | `file` / `redis` | Driver session. |
| `QUEUE_CONNECTION` | `sync` / `redis` | Antrean. `redis` butuh worker `queue` (profile `redis`). |
| `REDIS_HOST` | `redis` | Host Redis **di dalam jaringan compose** (bukan `127.0.0.1`). |
| `JENIS_RAWAT_IGD` / `JENIS_RAWAT_RI` / `JENIS_RAWAT_RJ` | `IGD` / `RI` / `RJ` | Kode jenis rawat untuk filter pendaftaran / EMR. |
| `FORM_ID_SOAP` | `2` | ID form SOAP/CPPT di tabel `form`. |
| `FORM_ID_PENGKAJIAN_AWAL_KEPERAWATAN` | `3` | ID form pengkajian awal keperawatan. |
| `FORM_ID_CATATAN_AWAL_MEDIS` | `1` | ID form catatan awal medis. |
| `FORM_ID_PENGKAJIAN_HARIAN_KEPERAWATAN` | `4` | ID form pengkajian harian keperawatan. |
| `OBJEK_ID_*` | `6` (sistolik), dst. | ID tiap field objek EMR di tabel `objek` (SOAP, vital sign, pengkajian, dsb.). |
| `PROFESI_ID_DOKTER` | `1` | ID profesi dokter di tabel `profesi`. |
| `REF_BAGIAN_RAJAL` | `1` | ID referensi bagian rawat jalan. |

> **Penting:** konstanta SIMRS dibaca langsung via `env()` saat runtime (bukan `config()`). Jangan menjalankan `config:cache` ketika nilai di atas belum di-set di `.env`, karena aplikasi akan kehilangan konstanta tersebut. Setelah mengubah `.env` pada mode Docker, selalu `docker compose up -d --force-recreate app web`.

---

## 10. Troubleshooting

| Masalah | Solusi |
| :--- | :--- |
| `Connection refused` ke database | PostgreSQL harus bisa dijangkau dari container. Uji: `docker compose exec app php -r 'echo gethostbyname("host.docker.internal");'`. Pastikan `sudo bash deploy/setup-postgres.sh` sudah dijalankan (aturan pg_hba untuk subnet Docker) dan `DB_*` di `.env` benar. |
| `password authentication failed` untuk user `meditech` | Password di `.env` tidak sama dengan yang dibuat skrip. Jalankan ulang `sudo bash deploy/setup-postgres.sh` (skrip menyinkronkan password) atau ubah password di `.env`. |
| `could not translate host name "host.docker.internal"` | `extra_hosts` belum ada di service `app`/`queue`. Pastikan `docker-compose.yml` memuat `extra_hosts: ["host.docker.internal:host-gateway"]`, lalu recreate container. |
| Perubahan `.env` tidak berpengaruh | Nilai `.env` dibaca saat container start. Jalankan `docker compose up -d --force-recreate app web`, lalu `docker compose exec app php artisan config:clear && docker compose exec app php artisan cache:clear`. |
| Halaman menampilkan source PHP (tidak dieksekusi) | Modul `proxy_fcgi` belum aktif. Rebuild image: `docker compose up -d --build web`, lalu cek `docker compose logs web`. |
| Apache 502 / "Service Unavailable" | Container `app` (PHP-FPM) mati atau belum siap. Cek `docker compose logs app` dan `docker compose ps`. |
| Error `Class "Redis" not found` / koneksi Redis refused | Ekstensi `php8.5-redis`/phpredis belum ada atau container `redis` belum jalan. Jalankan `docker compose --profile redis up -d` dan pastikan `.env` memakai `REDIS_HOST=redis` (bukan `127.0.0.1`). |
| Ingin mengaktifkan Redis | `docker compose --profile redis up -d`, lalu di `.env`: `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`. |
| Cache/session lama masih ada setelah pindah ke Redis | `docker compose exec redis redis-cli -n 0 FLUSHDB` (session+queue) dan `-n 1 FLUSHDB` (cache). |
| Aset frontend tidak termuat / tampilan polos | Pastikan container `vite` berjalan (`docker compose ps`, log `docker compose logs -f vite`). Untuk mode statis: `docker compose exec vite npm run build`. |
| Aset frontend tidak termuat **saat dibuka dari device lain via IP** | Dev server Vite (`port 5173`) harus jalan & tidak diblokir firewall. IP LAN ditulis otomatis ke `public/hot`; host request tetap dipakai sebagai host asset (lihat §6.5). Cek: `cat public/hot` lalu `curl -I http://<IP>:5173/@vite/client`. |
| Tampilan di HP terpotong / melebar | Sidebar di bawah 1024px menjadi drawer (butuh tombol hamburger). Tabel lebar digeser horizontal. Kalau tabel masih terpotong, pastikan `resources/js/app.js` termuat (build ulang: `docker compose exec vite npm run build`). Lihat §6.6. |
| **Ikon sidebar (Font Awesome) tidak muncul** di device lain | Webfont di-load dari `url()` pada CSS. Pastikan `vite.config.js` punya `server.origin: ''` (URL relatif) — kalau origin terisi, Vite menulis `url(http://0.0.0.0:5173/.../fa-solid-900.woff2)` yang hanya jalan di komputer server. Cek: `curl -s http://<IP>:5173/resources/css/app.css \| grep webfonts` → harus relatif (`/node_modules/...`). |
| Halaman 404 / route tidak ada untuk sub-menu baru | Setelah membuat/merubah `sub_menu`, provider auto-route aktif di request berikutnya; pastikan `file_sub_menu` terisi benar dan basename unik. Bila memakai `route:cache`, lakukan `route:clear` lalu `route:cache` lagi. |
| Sidebar tidak menampilkan sub-menu baru | Clear cache sidebar: logout lalu login ulang (logout melakukan `Cache::forget`), atau hapus key `sidebar_moduls_user_{id}` di level aplikasi / setelah ubah `user_akses`. |
| `migrate:fresh` menjatuhkan semua data | `migrate:fresh` akan **DROP SEMUA tabel** termasuk tabel legacy yang tidak punya migrasi. Pastikan kamu menyadarinya; untuk database berisi data gunakan `migrate` biasa. |
| Test gagal karena database | `phpunit.xml` memakai `sqlite :memory:`. Gunakan `php artisan test` sesuai konfigurasi; kode yang bergantung database nyata tidak akan berjalan apa adanya di bawah test. |
| `config:cache` membuat konstanta SIMRS hilang | Jangan cache config tanpa konstanta `.env` (lihat bagian Konfigurasi Environment). |

---

Dokumen ini ditulis dari kondisi aktual proyek. Sebelum mengubah kode, baca juga `AGENTS.md` untuk konvensi pengembangan terkini (schema, naming, auto-route, seeder, dan aturan-aturan lainnya).