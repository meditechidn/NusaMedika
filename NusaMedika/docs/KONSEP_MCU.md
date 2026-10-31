# KONSEP MODUL MEDICAL CHECKUP (MCU)

> **Pembaruan:** tabel `bill_temp` dan model `BillTemp` sudah dihapus. Billing disatukan di tabel `billing` (penanda selesai = `billing.status_selesai`). Referensi `bill_temp` di bawah adalah konteks historis.

> Status: **Rancangan / Konsep** — belum diimplementasikan.
> Dokumen ini menjadi acuan implementasi. Semua nama (folder, route, slug, sub_menu, modul_id/menu_id/sub_menu_id) bersifat keputusan awal dan harus disepakati sebelum kode dibuat.

---

## 1. Ringkasan

Modul **Medical Checkup (MCU)** adalah modul baru yang berdiri **sendiri di sidebar** (bukan bagian dari Rawat Jalan), tetapi **konsep pendaftaran dan pelayanannya identik dengan Rawat Jalan**:

- **Pendaftaran MCU diletakkan di module Registrasi → menu Pendaftaran** — setara `Daftar Rawat Jalan` (sub_menu `Registrasi/Pendaftaran/DaftarRajal`), tapi dengan `jenis_rawat=MCU`.
- **Modul `Medical Checkup` hanya berisi menu Pasien** (List Pasien Medical Checkup) untuk **melayani pasien** — setara `Rawat Jalan → Pasien → List Pasien`, tapi filter `jenis_rawat=MCU`.
- **Bagian MCU masuk `referensi_bagian` = RAWAT JALAN** (`referensi_bagian_id=1`) — secara klinis MCU adalah layanan rawat jalan, sehingga tabel `bagian` tidak butuh referensi baru.
- **`registrasi.jenis_rawat` = `MCU`** (konstanta `.env` `JENIS_RAWAT_MCU`, default `'MCU'`) — nilai yang **berbeda dari** `RJ`, jadi pasien MCU tidak tercampur dengan pasien rawat jalan biasa di List Pasien RJ / List Pasien Dokter.
- **Satu tabel baru: `suket_mcu`** — master surat keterangan MCU (nama + harga). Saat **Daftar Medical Checkup**, petugas memilih **jenis suket** dari master ini (mis. Surat Keterangan Sehat); referensinya + snapshot harga disimpan di `registrasi_detail`.
- Sisanya **reuse tabel existing**: `registrasi`, `registrasi_detail`, `bill_temp`, `registrasi_urut`, `diagnosa_rawat`, `penanggung_rawat`, `jadwal_dokter`, `nasabah`, `pasien_nasabah`, `kelas_ruang`, `icd`, `emr`, `form`.
- **EMR pasien MCU** tampil seperti biasa: `EmrDashboardController` sudah support `jenis_rawat='4'/'mcu'/'MCU'` → filter `form.mcu=1`. Form baseline yang `mcu=1` (Catatan Awal Medis, SOAP, Pengkajian, Order Lab/Rad/Resep) langsung tersedia; form khusus MCU bisa ditambahkan di iterasi lanjutan.

Alur data satu kunjungan MCU = **persis** alur Rawat Jalan (`DaftarRajalController::store`):

```
registrasi (jenis_rawat=MCU)
 ├─ registrasi_detail (bagian = poli MCU)
 ├─ bill_temp
 ├─ registrasi_urut (antrian dokter MCU)
 ├─ diagnosa_rawat
 └─ penanggung_rawat (dokter MCU / rawat_user)
```

---

## 2. Tujuan & Manfaat

| Tujuan | Manfaat |
|---|---|
| MCU tersendiri di sidebar, tidak bercampur Rawat Jalan | Petugas MCU terpisah dari antrian poli biasa; fokus pada paket checkup |
| Pendaftaran MCU sama dengan Rawat Jalan | User tidak perlu belajar alur baru; reuse validasi & relasi existing |
| `jenis_rawat=MCU` dipisah dari `RJ` | Pasien MCU tidak masuk List Pasien Dokter/List Pasien RJ; tidak mengacak antrian poli reguler |
| Bagian MCU ref `RAWAT JALAN` | Tanpa referensi bagian baru; sama-sama layanan ambulatori |
| EMR otomatis aktif (flag `form.mcu=1`) | Dokter MCU bisa isi Catatan Awal Medis/SOAP/order dari dashboard pasien yang sama |
| Billing (bill_temp/penanggung_rawat) reuse | Rencana billing MCU konsisten dengan RJ |

---

## 3. Struktur Modul & Sidebar

```
Registrasi              (modul 1)
└── Pendaftaran          (menu 2)
     ├── List Pelayanan Pasien             (sub 3)
     ├── Daftar Rawat Jalan                (sub 4)
     ├── Daftar Rawat Inap                 (sub 5)
     ├── Registrasi IGD                    (sub 6)
     ├── Registrasi IGD Obgyn              (sub 7)
     └── Daftar Medical Checkup            (sub_menu_id 48)  ← BARU
         file_sub_menu = Registrasi/Pendaftaran/DaftarMedicalCheckup/daftar_medical_checkup

Medical Checkup          (modul_id 9, icon fa-solid fa-heart-pulse, urutan_modul 3 → setelah Rawat Jalan)
└── Pasien                (menu_id 17)
     └── List Pasien Medical Checkup      (sub_menu_id 49)
         file_sub_menu = MedicalCheckup/Pasien/ListPasienMedicalCheckup/list_pasien_medical_checkup
```

- **Pendaftaran MCU hidup di module Registrasi → Pendaftaran** (setara Daftar Rajal), karena pendaftaran adalah aktivitas registrasi.
- **Modul Medical Checkup hanya untuk pelayanan** (melayani pasien MCU), setara `Rawat Jalan → Pasien`.
- Leaf folder harus **kata utuh tanpa akronim** (aturan *Modular structure*): `DaftarMedicalCheckup`, `ListPasienMedicalCheckup` — **jangan** `DaftarMCU`/`ListPasienMcu` (basename jadi `daftar_m_c_u`).
- Controller:
  - `App\Http\Controllers\Registrasi\Pendaftaran\DaftarMedicalCheckup\DaftarMedicalCheckupController`
  - `App\Http\Controllers\MedicalCheckup\Pasien\ListPasienMedicalCheckup\ListPasienMedicalCheckupController`
- Views: `resources/views/moduls/Registrasi/Pendaftaran/DaftarMedicalCheckup/daftar_medical_checkup{,_create}.blade.php` dan `resources/views/moduls/MedicalCheckup/Pasien/ListPasienMedicalCheckup/list_pasien_medical_checkup.blade.php`
- Route auto (dari basename): `daftar_medical_checkup.*`, `list_pasien_medical_checkup.*`.
- Nama route pendaftaran MCU satu kelompok dengan registrasi lain: `daftar_medical_checkup.store/index`.

Tampilan sidebar pasca-implementasi:

```
Registrasi                (modul 1)
 └── Pendaftaran
      ├── List Pelayanan Pasien
      ├── Daftar Rawat Jalan        (jenis_rawat=RJ)
      ├── Daftar Rawat Inap
      ├── Registrasi IGD
      ├── Registrasi IGD Obgyn
      └── Daftar Medical Checkup    (jenis_rawat=MCU)  ← BARU
...
Medical Checkup           (modul 9)  ← BARU
 └── Pasien
      └── List Pasien Medical Checkup (jenis_rawat=MCU)
...
Administrator             (modul 5)
 └── Manajemen Master
      └── Master Suket Medical Checkup  ← BARU (master jenis + harga suket)
```

---

## 4. Bagian MCU (`bagian` + `referensi_bagian`)

### 4.1 Referensi tetap `1 = RAWAT JALAN` (tidak ada baris baru)

`referensi_bagian` sudah punya `1 = RAWAT JALAN`. Bagian MCU tidak perlu referensi sendiri karena layanannya ambulatori.

### 4.2 Append 1 baris di `BagianSeeder` (`$rajals`)

| bagian_id | nama_bagian | referensi_bagian_id |
|---|---|---|
| **39** | **POLI MEDICAL CHECKUP** | 1 |

> **SELALU append di akhir** list bagian agar `bagian_id` existing tidak bergeser. `bagian_id=39` adalah keputusan awal (check dulu id maksimal di DB saat implementasi; seeder memakai insert eksplisit ber-id berurutan dari 1, jadi id riil mengikuti baris terakhir di `BagianSeeder`). Jika ingin lebih dari satu poli MCU (mis. MCU Umum / MCU Khusus), tambahkan baris lagi dengan nama mengandung `MEDICAL CHECKUP` dan filter memakai `LIKE '%MEDICAL CHECKUP%'`.

Karena `referensi_bagian_id=1`, poli MCU **secara otomatis muncul** di semua dropdown/komponen yang memfilter `referensi_bagian_id=1` (`x-select_poliklinik`, dropdown poli DaftarRajal, dst.). Pemisahan dilakukan lewat **`r.jenis_rawat=MCU`** pada query list, dan pada **form pendaftaran RJ eksisting dilakukan pengecualian poli MCU** (lihat §10).

---

## 5. Konstanta `.env` — `JENIS_RAWAT_MCU`

Tambahkan di `.env` **dan** `.env.example`:

```ini
JENIS_RAWAT_MCU=MCU
```

Setelah edit `.env`, wajib recreate container agar nilai terbaca:
`docker compose up -d --force-recreate app vite`.

**Integrasi existing yang langsung berfungsi** (tanpa ubah kode):

- `EmrDashboardController::index` (baris 39–40): `jenis_rawat == '4' || 'mcu' || env('JENIS_RAWAT_MCU','MCU')` → `$query->where('form.mcu','1')`. Jadi dashboard pasien MCU otomatis memfilter form ber-flag `mcu=1`.
- `form.mcu` (`0/1`, nullable) pada tabel `form` + checkbox MCU di Manajemen EMR → Form sudah ada.
- `nasabah_create.blade.php` sudah mencantumkan `env('JENIS_RAWAT_MCU') => 'MCU'` pada checkbox instalasi nasabah.

> **Jangan** `config:cache` tanpa `.env` lengkap — aturan umum konstanta SIMRS di README/AGENTS.

---

## 6. Pendaftaran Medical Checkup (mirror DaftarRajal — Registrasi → Pendaftaran)

Controller `Registrasi\Pendaftaran\DaftarMedicalCheckup\DaftarMedicalCheckupController` meniru `DaftarRajalController` **hampir 1:1**, perbedaan hanya:

| Aspek | Rawat Jalan (`DaftarRajal`) | Medical Checkup (`DaftarMedicalCheckup`) |
|---|---|---|
| `registrasi.jenis_rawat` | `env('JENIS_RAWAT_RJ')` | `env('JENIS_RAWAT_MCU','MCU')` |
| Sumber poli | semua jadwal aktif (`JadwalDokter` all) | hanya jadwal yang `bagian` ber-nama `%MEDICAL CHECKUP%` (ref 1) |
| View | `daftar_rajal` | `daftar_medical_checkup` |

### 6.1 `index()`

```
$jadwals = JadwalDokter::aktif()
    ->whereHas('bagian', fn ($q) => $q->aktif()->where('referensi_bagian_id', 1)->where('nama_bagian','like','%MEDICAL CHECKUP%'))
    ->with(['pegawai','bagian'])->orderBy('hari')->orderBy('waktu_mulai')->get();
$polikliniks   = poly MCU unik dari jadwal
$jadwalsByPoli = grup jadwal per poli (hari/jam/dokter/kuota), tampilkan tabel jadwal MCU
$nasabahs      = nasabah aktif (sama)
$sukets        = SuketMcu::aktif()->orderBy('nama_suket')->get();  // dropdown jenis suket
```

### 6.2 `store()` — pola DaftarRajal dalam satu transaksi

Validasi (sama dengan DaftarRajal):
`pasien_id`, `tgl_kunjungan`, `poliklinik`, `jadwal_dokter_id` (exists), `nasabah_id`, `cara_masuk`, `icd_id`, `keluhan` — **ditambah**:
- `suket_mcu_id` **optional** (`nullable|exists:suket_mcu,suket_mcu_id`) — jenis surat keterangan yang diminta pasien (memakai dropdown dari master suket; boleh kosong bila pasien tidak minta suket);
- cek `jadwal_dokter.bagian_id` adalah poli MCU (anti simpan ke poli non-MCU).

Urutan insert (persis `DaftarRajalController::store`):
1. `PasienNasabah` find-or-create (pasien+nasabah).
2. `Registrasi`: `tgl_masuk = tgl_kunjungan.H:i:s`, **`jenis_rawat = env('JENIS_RAWAT_MCU','MCU')`**, `prioritas=cara_masuk`, `pasien_nasabah_id`, `memo=keluhan`.
3. `RegistrasiDetail`: `bagian_id=poliklinik` (poli MCU), snapshot `kelas_id`/`hak_kelas_id` dari hak kelas nasabah (`KelasRuang`), `terima_dari='DALAM'`, **plus kolom suket**: `suket_mcu_id` + **snapshot `harga_suket`** (dari record master saat pendaftaran — harga bisa berubah di kemudian hari, snapshot menjaga nilai billing pada saat itu).
4. `BillTemp`: snapshot kelas/nasabah, `status_selesai=0`.
5. `RegistrasiUrut`: `urutan = GenerateHelper::generateNoUrut($dokter, $poliklinik, $tgl)`; `tgl_urut = hitungEstimasi` — antrian MCU menghitung di poli MCU, konsisten dengan antrian poli lain.
6. `DiagnosaRawat`: `icd_id`, `jenis_diagnosa=1`.
7. `PenanggungRawat`: `rawat_user_id = user dokter MCU` (dari `jadwal_dokter.pegawai_id` → `users.pegawai_id`), fallback `auth()->id()`.

Cek duplikat aktif (pasien + tgl) tetap dipakai → pesan "Pasien telah memiliki pendaftaran aktif pada periode kunjungan ...".

Flash sukses → redirect ke **`list_pasien_medical_checkup.index`** (bukan list pelayanan pasien).

---

## 7. List Pasien Medical Checkup / Pelayanan (mirror ListPasienRajal)

Controller `ListPasienMedicalCheckupController` meniru `ListPasienRajalController`. Perbedaan: filter `r.jenis_rawat = env('JENIS_RAWAT_MCU','MCU')` dan dropdown poli hanya poli MCU.

### 7.1 `index()` — query JOIN keras (sama dengan ListPasienRajal)

```php
DB::table('registrasi as r')
    ->join('registrasi_detail as rd', 'rd.registrasi_id','=','r.registrasi_id')
    ->join('pasien as p', 'p.pasien_id','=','r.pasien_id')
    ->join('bagian as b', 'b.bagian_id','=','rd.bagian_id')
    ->join('pasien_nasabah as pn', 'pn.pasien_nasabah_id','=','r.pasien_nasabah_id')
    ->join('nasabah as n', 'n.nasabah_id','=','pn.nasabah_id')
    ->join('bill_temp as bt', 'bt.registrasi_detail_id','=','rd.registrasi_detail_id')
    ->join('registrasi_urut as ru', 'ru.registrasi_detail_id','=','rd.registrasi_detail_id')
    ->join('penanggung_rawat as pr', 'pr.registrasi_id','=','r.registrasi_id')
    ->leftJoin('suket_mcu as sk', 'sk.suket_mcu_id','=','rd.suket_mcu_id') // LEFT JOIN — suket optional
    ->whereDate('r.tgl_masuk', $tanggalKunjungan)
    ->where('rd.bagian_id', $poliklinikMcuId)          // poli MCU terpilih
    ->where('r.jenis_rawat', env('JENIS_RAWAT_MCU','MCU')); // ← beda dari RJ
// + filter status_batal != 1 OR NULL di ke-10 tabel (jangan whereNull saja)
```

- Filter: `tanggal_kunjungan`, `poliklinik` (dropdown poli MCU), `dokter_id` opsional (filter `pr.rawat_user_id`).
- Kolom: No MR, nama + umur pasien, nama poli, nasabah, **jenis suket** (`sk.nama_suket` + `rd.harga_suket` format Rupiah; "-" bila tidak diminta), urutan antrian, status billing, **badge status pelayanan medis** (SOAP/Pengkajian sudah terisi — reuse pola `emr_forms` di `ListPasienRajalController:89–111`), aksi **Buka Dashboard Pasien** → `route('dashboard_pasien.index', $rd->registrasi_detail_id)`.
- `$dokter` dropdown = user dokter dari jadwal MCU (`pegawai` profesi=1).

### 7.2 Melayani pasien

Klik **EMR** pada baris → `EmrDashboardController` (filter `form.mcu=1`) → isi SOAP/Catatan Awal Medis/Order — **tanpa kode baru** (flag `mcu=1` sudah ada di form baseline 1–6).

---

## 8. Integrasi EMR / Dashboard Pasien

- `registrasi.jenis_rawat='MCU'` → `EmrDashboardController` baris 39–40 (sudah ada) → form `mcu=1`. Baseline yang langsung tampil: Catatan Awal Medis (1), SOAP (2), Pengkajian Awal/Harian (3/4), Order Resep (5), Order Lab (6), Order Rad (7) — **kecuali** Konsultasi (8, `mcu=0`).
- **Opsional (iterasi 2):** form EMR khusus MCU (mis. `medical_checkup` — anamnesis, pemeriksaan fisik, hasil lab checkup, kesimpulan) dibuat lewat **Manajemen EMR → Form** (tambah `form`, `objek`, `objek_form_control`, set `mcu=1`) + `DynamicFormController` generik — tanpa route/controller manual. Cukup set `form.mcu=1` agar muncul di dashboard pasien MCU saja.

---

## 9. Master Suket Medical Checkup (Administrator → Manajemen Master)

Daftar Medical Checkup memerlukan **jenis surat keterangan (suket)** yang bisa dipilih (mis. Surat Keterangan Sehat) beserta **harganya**. Data tersebut dikelola sebagai master CRUD di modul Administrator.

### 9.1 Sub Menu "Master Suket Medical Checkup"

```
Administrator            (modul 5)
└── Manajemen Master      (menu 6)
     └── Master Suket Medical Checkup   (sub_menu_id 50, urutan_sub_menu 18)
         file_sub_menu = Administrator/ManajemenMaster/SuketMedicalCheckup/suket_medical_checkup
```

- Controller: `App\Http\Controllers\Administrator\ManajemenMaster\SuketMedicalCheckup\SuketMedicalCheckupController` (CRUD auto route `admin.suket_medical_checkup.*`, URI `/suket_medical_checkup` — pola `KelasController`/`IcdController`).
- Views: `resources/views/moduls/administrator/manajemen_master/suket_medical_checkup/{index,create,edit}.blade.php`.
- Leaf `SuketMedicalCheckup` = kata utuh (bukan akronim), unik di seluruh app.

### 9.2 Tabel baru `suket_mcu` + Model

Migration baru (mis. `2026_09_20_000001_create_suket_mcu_table`):

| kolom | tipe | keterangan |
|---|---|---|
| `suket_mcu_id` | `increments` PK | auto-increment |
| `nama_suket` | string(150) | e.g. "Surat Keterangan Sehat" |
| `harga` | decimal(12,2) | tarif suket |
| `input_time` / `mod_time` | timestamp(6) nullable | kolom audit |
| `input_user_id` / `mod_user_id` | int nullable | kolom audit |
| `status_batal` | smallint nullable | soft-delete (0/1) |

Model `App\Models\SuketMcu`:

```php
class SuketMcu extends Model
{
    protected $primaryKey = 'suket_mcu_id';
    public $timestamps = false;
    protected $fillable = ['nama_suket', 'harga', 'status_batal']; // contoh
    // + scope aktif() (status_batal != 1 OR NULL) seperti model lain
}
```

### 9.3 CRUD controller (pola Kelas / ICD)

- `index()` — list record aktif (`status_batal != 1 OR NULL`), kolom: Nama Suket, Harga (format Rupiah), Status, Aksi.
- `store()/update()` — validasi `nama_suket` (required), `harga` (required, numeric ≥ 0); tulis dalam `DB::beginTransaction()`; PK via auto-increment (tanpa `getNextId`); clear sidebar cache (semua user) seperti CRUD administrator lain.
- `destroy()` — soft-delete (`status_batal=1`).
- Dropdown harga/format di view pakai helper format rupiah existing (pola `kelas/index.blade.php`).

### 9.4 Seeder `SuketMcuSeeder` (data contoh)

| suket_mcu_id | nama_suket | harga |
|---|---|---|
| 1 | Surat Keterangan Sehat | 50.000 |
| 2 | Surat Keterangan Bebas Narkoba | 100.000 |

Daftarkan di `DatabaseSeeder` (setelah `KelasRuangSeeder`) + tambahkan `suket_mcu` ke daftar `GenerateHelper::resetSequence()`. Seeder idempotent (`updateOrInsert` key PK).

---

## 10. Perubahan pada Fitur Existing (anti-campur MCU ↔ RJ)

Karena poli MCU ref `RAWAT JALAN`, beberapa halaman RJ yang memfilter `referensi_bagian_id=1` **tanpa** filter jenis_rawat akan ikut menampilkan poli MCU:

1. **`DaftarRajalController::index/store`** — dropdown poli dibangun dari jadwal semua bagian. Filter agar **mengecualikan** poli MCU:
   ```php
   ->where('b.nama_bagian', 'not like', '%MEDICAL CHECKUP%')   // pada relasi bagian
   ```
   (alternatif: `whereDoesntHave('bagian', fn($q)=>$q->where('nama_bagian','like','%MEDICAL CHECKUP%'))`). Validasi `store()` menolak `jadwal_dokter` yang bagiannya poli MCU.
2. **`x-select_poliklinik`** (dipakai form RJ lain) — diputuskan apakah MCU perlu dieksklusikan; karena `registrasi.jenis_rawat` ya tetap `RJ`/`MCU`, pasien tidak akan tercampur di list, tapi dropdown RJ akan menampilkan poli MCU. **Keputusan:** untuk konsistensi, filter nama `%MEDICAL CHECKUP%` dihapus dari dropdown RJ (lihat §13 item 4).
3. **`ListPelayananPasien`** (`Registrasi → Pendaftaran → List Pelayanan Pasien`) — tambahkan opsi dropdown `MCU`:
   ```blade
   <option value="{{ env('JENIS_RAWAT_MCU', 'MCU') }}" @selected(request('jenis_layanan')==env('JENIS_RAWAT_MCU','MCU'))>MEDICAL CHECKUP</option>
   ```
   Controller sudah filter `where('jenis_rawat', $jenisLayanan)` — tidak perlu ubah.
4. **`ListPasienRajal` / `ListPasienDokter`** — tidak perlu ubah (filter `r.jenis_rawat = env('JENIS_RAWAT_RJ')` sudah otomatis mengecualikan MCU).

---

## 11. Seeding — Ringkasan Perubahan

| Seeder | Perubahan |
|---|---|
| `BagianSeeder` | append `POLI MEDICAL CHECKUP` (id 39, `referensi_bagian_id=1`) di array `$rajals` |
| `ModulMenuSubMenuSeeder` | modul 9 "Medical Checkup" (`fa-solid fa-heart-pulse`) + menu 17 "Pasien" + sub 49 `MedicalCheckup/Pasien/ListPasienMedicalCheckup/list_pasien_medical_checkup`; **plus** sub 48 `Registrasi/Pendaftaran/DaftarMedicalCheckup/daftar_medical_checkup` di menu 2 (Pendaftaran, urutan 6); **plus** sub 50 "Master Suket Medical Checkup" `Administrator/ManajemenMaster/SuketMedicalCheckup/suket_medical_checkup` di menu 6 (Manajemen Master, urutan 18); lalu `php artisan seeder:sync-master-menu` |
| `SuketMcuSeeder` (baru) | seed `suket_mcu` 1 "Surat Keterangan Sehat" 50.000 & 2 "Surat Keterangan Bebas Narkoba" 100.000 |
| `UserSeeder` | admin otomatis mendapat sub 48, 49, 50 (dinamis) — tidak perlu ubah manual |
| `.env` / `.env.example` | tambah `JENIS_RAWAT_MCU=MCU` |

**Migration baru (2):**

1. `2026_09_20_000001_create_suket_mcu_table` — tabel master suket (lihat 9.2).
2. `2026_09_20_000002_add_suket_to_registrasi_detail_table` — tambah kolom nullable `suket_mcu_id` (int) + `harga_suket` (decimal(12,2)) pada `registrasi_detail` untuk snapshot jenis suket & harganya saat pendaftaran MCU.

`DatabaseSeeder`/`GenerateHelper::resetSequence()`: tambahkan `suket_mcu` ke daftar reset (tabel `registrasi_detail` sudah terdaftar).

---

## 12. Route

Auto-router (`SubMenuRouteServiceProvider`) menurunkan CRUD dari `sub_menu` — **tidak perlu route manual** untuk index/store/list. `store` pendaftaran memakai route `daftar_medical_checkup.store` (auto dari sub 48, URI `/daftar_medical_checkup` — sejajar `/daftar_rajal`); list pasien memakai `list_pasien_medical_checkup.index` (auto dari sub 49); master suket memakai CRUD `admin.suket_medical_checkup.*` (auto dari sub 50, URI `/suket_medical_checkup`).

> Kontroller pendaftaran cukup method `index` + `store`; kontroler list pasien cukup `index`; kontroler master suket lengkap `index/create/store/edit/update/destroy`. Route otomatis hanya didaftarkan untuk method yang ada (`method_exists`).

---

## 13. Risiko, Konsekuensi & Keputusan yang Harus Disepakati

1. **`jenis_rawat=MCU` wajib berbeda dari `RJ`** — kalau tertukar, pasien MCU muncul di List Pasien RJ/Dokter dan antri dengan pasien reguler. Konstanta `.env` dipakai sebagai source of truth (`env('JENIS_RAWAT_MCU','MCU')`).
2. **Poli MCU ref `RAWAT JALAN` → muncul di dropdown RJ** — karena filter banyak komponen `x-select_poliklinik`/DaftarRajal hanya `referensi_bagian_id=1`. Wajib ada pengecualian `%MEDICAL CHECKUP%` pada DaftarRajal (§10) agar petugas tidak salah mendaftarkan pasien RJ ke poli MCU.
3. **`bagian_id` poli MCU append di akhir** `BagianSeeder` — seeder menghapus & men-seed ulang `$rajals` dengan id berurutan; append menjaga id existing tidak bergeser.
4. **Aturan "1 pasien = 1 registrasi aktif per tgl" tetap berlaku** — cek aktif DaftarRajal (per pasien+tgl) dipakai juga di MCU; pasien yang sudah RJ di hari yang sama **tidak boleh** didaftarkan MCU di hari yang sama (atau sebaliknya) — perbaiki cek agar mengecek **keduanya** (RJ **dan** MCU).
5. **Jadwal dokter MCU** = baris `jadwal_dokter` biasa dengan `bagian_id` poli MCU (dikelola Master Jadwal Dokter existing). Dokter MCU bisa juga praktik RJ → antrian terpisah karena `registrasi_urut.bagian_id`/`pegawai_id` berbeda.
6. **EMR MCU = flag `form.mcu=1`** — jangan mengubah konstanta `JENIS_RAWAT` di `EmrDashboardController`; cuma menambah konstanta env. Form baseline sudah `mcu=1`; form 8 Konsultasi sengaja `mcu=0`.
7. **Leaf harus kata utuh** — `DaftarMedicalCheckup`/`ListPasienMedicalCheckup`, bukan `DaftarMCU` (basename jadi `daftar_m_c_u`).
8. **Billing** — `BillTemp` MCU dibuat seperti RJ; pastikan paket MCU (multi-tindakan) + biaya suket ditagih via modul billing/tindakan di iterasi 2 (bukan hardcode di registrasi).
9. **Suket = snapshot harga** — `registrasi_detail.harga_suket` diisi dari master saat pendaftaran; perubahan harga master di kemudian hari **tidak** mengubah tagihan yang sudah terbit. `suket_mcu_id` optional (pasien boleh tidak minta suket) → kolom & dropdown nullable, LEFT JOIN di list.
10. **modul_id/menu_id/sub_menu_id must be free** — 9/17/48/49/50 bebas saat dokumen ini ditulis; cek ulang sebelum migrate/seed.

---

## 14. Rencana Implementasi Bertahap

### Iterasi 1 (inti — registrasi + list pasien + EMR jalan)
1. `.env` + `.env.example`: `JENIS_RAWAT_MCU=MCU`; recreate container.
2. `BagianSeeder`: append `POLI MEDICAL CHECKUP` (`referensi_bagian_id=1`); tambah 1–2 jadwal dokter MCU di data jadwal (atau lewat Master Jadwal Dokter).
3. Migration `suket_mcu` + `SuketMcuSeeder` + `SuketMcuController` (CRUD `admin.suket_medical_checkup.*`) + sub_menu 50 "Master Suket Medical Checkup".
4. Migration kolom `suket_mcu_id`/`harga_suket` di `registrasi_detail`.
5. `DaftarMedicalCheckupController` + view `daftar_medical_checkup{,_create}.blade.php` (mirror DaftarRajal; `jenis_rawat=MCU`; dropdown jenis suket; cek duplikat lintas RJ+MCU).
6. `ListPasienMedicalCheckupController` + view `list_pasien_medical_checkup.blade.php` (mirror ListPasienRajal; JOIN 10 tabel; kolom jenis suket + harga; badge status medis; tombol EMR).
7. `ModulMenuSubMenuSeeder`: modul 9/menu 17/sub 48–50 + `seeder:sync-master-menu` + clear sidebar cache + `route:clear`/`cache:clear`.
8. Pengecualian poli MCU di `DaftarRajalController` (hasilkan daftar poli/poly dropdown tanpa `%MEDICAL CHECKUP%`, tolak jadwal MCU di validasi store).
9. Opsi dropdown `MEDICAL CHECKUP` di ListPelayananPasien.

### Iterasi 2 (penguatan)
- Form EMR khusus MCU (Manajemen EMR → Form): `medical_checkup` (anamnesis, pemeriksaan, hasil, kesimpulan) — otomatis via `DynamicFormController`, tanpa controller manual.
- **Cetak Surat Keterangan** — dari List Pasien Medical Checkup, tombol "Cetak Suket" memakai `rd.suket_mcu_id` + data pasien + hasil EMR (slip surat keterangan sehat/bebas narkoba berisi ttd dokter).
- Master **paket MCU** (daftar tindakan/barang yang digabung, misal MCU Dasar/Lengkap) + billing multi-tindakan (termasuk biaya suket).
- Cetak hasil MCU (slip/hasil checkup untuk pasien).

---

## 15. Referensi Kode Existing yang Dipakai

- `App\Http\Controllers\Registrasi\Pendaftaran\DaftarRajal\DaftarRajalController` — blueprint pendaftaran `registrasi`+`registrasi_detail`+`bill_temp`+`registrasi_urut`+`diagnosa_rawat`+`penanggung_rawat` (diadaptasi: `jenis_rawat=MCU`, poli MCU, snapshot suket).
- `App\Http\Controllers\RawatJalan\Pasien\ListPasienRajal\ListPasienRajalController` — blueprint list pasien (JOIN keras 9 tabel, filter jenis_rawat, badge status medis via `EmrHelper::formIdBySlug`) + LEFT JOIN `suket_mcu`.
- `App\Http\Controllers\Administrator\ManajemenMaster\Kelas\KelasController` (atau `IcdController`) — blueprint CRUD master suket (`admin.suket_medical_checkup.*`).
- `App\Http\Controllers\EMR\EmrDashboard\EmrDashboardController` — sudah mensupport `jenis_rawat=MCU` (baris 39–40), tidak perlu diubah.
- `App\Helpers\GenerateHelper` — `generateNoUrut`, `hitungEstimasi` (antrian MCU).
- `App\Models\{Registrasi,RegistrasiDetail,BillTemp,RegistrasiUrut,DiagnosaRawat,PenanggungRawat,JadwalDokter,KelasRuang,PasienNasabah}` — model reuse, tanpa tabel baru (kecuali `SuketMcu` yang baru).
- `database/seeders/{BagianSeeder,SuketMcuSeeder,ModulMenuSubMenuSeeder}.php` + `php artisan seeder:sync-master-menu`.