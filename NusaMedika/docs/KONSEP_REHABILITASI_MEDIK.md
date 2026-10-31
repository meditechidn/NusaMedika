# KONSEP MODUL REHABILITASI MEDIK (Konsultasi, Bukan Order)

> **Pembaruan:** tabel `bill_temp` dan model `BillTemp` sudah dihapus. Billing disatukan di tabel `billing` (penanda selesai = `billing.status_selesai`). Referensi `bill_temp` di bawah adalah konteks historis.

> Status: **Rancangan / Konsep** — belum diimplementasikan.
> Dokumen ini menjadi acuan implementasi. Semua nama (folder, route, slug, sub_menu, id dashboard) bersifat keputusan awal dan harus disepakati sebelum kode dibuat.

---

## 1. Ringkasan

Fitur ini menambahkan **Rehabilitasi Medik** sebagai modul baru di dalam module **Penunjang Medis**, dengan perbedaan mendasar dari Laboratorium/Radiologi:

- **Lab/Rad** menerima **order** (tindakan + hasil) — `order_laboratorium`/`order_radiologi`.
- **Rehabilitasi Medik TIDAK menerima order**, melainkan menerima **Konsultasi**. Dokter klinis "memanggil" Rehabilitasi Medik lewat form EMR **Formulir → Konsultasi**; cukup dengan menerbitkan `registrasi_detail` baru (masih dalam `registrasi` yang sama), pasien otomatis masuk **Daftar Pasien Rehabilitasi Medik** di modul Penunjang Medis.

Form **Konsultasi** tersebut bersifat **multi-guna** — satu EMR dengan select **jenis konsultasi** yang berisi 3 opsi:

| Opsi | Perilaku |
|---|---|
| **Konsultasi Rehabilitasi Medik** | Menerbitkan `registrasi_detail` baru (bagian Rehabilitasi Medik) → muncul di sub menu **Daftar Pasien Rehabilitasi Medik** (masih satu `registrasi`). |
| **Konsul Layanan Klinik** (konsul internal Poli) | **Dokter tujuan wajib diisi.** Menerbitkan `registrasi_detail` baru (bagian Poli tujuan) + didaftarkan ke dokter tujuan → otomatis muncul di **List Pasien Dokter** poli tujuan (masih satu `registrasi`). |
| **Rencana Kontrol Rawat Jalan** | **Hanya menerbitkan EMR rencana kontrol** (tersimpan di `emr`/`emr_detail` dan **bisa dicetak** untuk diberikan ke pasien) — **TIDAK menerbitkan `registrasi`/`registrasi_detail` baru**. |

Aturan kunci:

- **Tidak ada tabel baru.** Semua reuse tabel existing: `registrasi`, `registrasi_detail`, `bill_temp`, `registrasi_urut`, `penanggung_rawat`, `emr`, `emr_detail`, `form`, `objek`, `objek_form_control`, `dashboard_menu*`, `akses_ehr`.
- **Konsul Rehabilitasi Medik & Konsul Layanan** = konsul *intra-registrasi* (satu `registrasi`, banyak `registrasi_detail`), sedangkan **Rencana Kontrol** hanya EMR rencana kontrol yang bisa dicetak — **tidak menerbitkan registrasi baru**.
- Entri data konsultasi tetap lewat **EMR** (`emr`/`emr_detail`); `registrasi_detail` tujuan hanya penanda/penerima layanan agar muncul di daftar pasien tujuan.

---

## 2. Tujuan & Manfaat

| Tujuan | Manfaat |
|---|---|
| Rehabilitasi Medik masuk alur penunjang tanpa order | Konsep "konsul" sesuai praktik klinis Rehab Medik (pasien di-rujuk, bukan kirim spesimen) |
| Satu form `Konsultasi` untuk 3 kebutuhan | Dokter tidak berpindah halaman; jenis tujuan dipilih dari dropdown |
| Konsul layanan poli otomatis muncul di List Pasien Dokter | Dokter konsulen langsung melihat pasien yang di-konsul tanpa reg manual |
| Konsul rehab otomatis muncul di Daftar Pasien Rehab Medik | Petugas rehab mendapat antrian pasien konsul |
| Rencana kontrol & konsul tercatat di EMR dan **ketiganya bisa dicetak** | Dokter menulis + mencetak langsung (lembar rencana kontrol diberikan ke pasien; slip rujukan konsul rehab/layanan sebagai bukti & instruksi ke bagian tujuan) |
| Riwayat tercatat di EMR (`Formulir → Konsultasi`) | Kontinuitas rekam medis; semua konsultasi terekam & bisa diverifikasi akses |

---

## 3. Struktur Modul & Sidebar

### 3.1 Modul Penunjang Medis — menu baru "Rehabilitasi Medik"

```
Penunjang Medis  (modul_id 8, icon fa-solid fa-microscope)
├── Laboratorium             (menu_id 14)  → Daftar Pesanan Laboratorium   (sub 44)
├── Radiologi                (menu_id 15)  → Daftar Pesanan Radiologi      (sub 45)
└── Rehabilitasi Medik       (menu_id 16)  → Daftar Pasien Rehabilitasi Medik (sub 47)
    file_sub_menu = PenunjangMedis/RehabilitasiMedik/DaftarPasienRehabilitasiMedik/daftar_pasien_rehabilitasi_medik
```

- `menu_id = 16`, `sub_menu_id = 47` (id bebas setelah 46).
- Controller: `App\Http\Controllers\PenunjangMedis\RehabilitasiMedik\DaftarPasienRehabilitasiMedik\DaftarPasienRehabilitasiMedikController`
- Views: `resources/views/moduls/PenunjangMedis/RehabilitasiMedik/DaftarPasienRehabilitasiMedik/{index,detail}.blade.php`
- Route auto (dari basename): `daftar_pasien_rehabilitasi_medik.*`.
- Leaf `DaftarPasienRehabilitasiMedik` = kata utuh & unik (aman sesuai aturan *Modular structure*).

### 3.2 Dashboard EMR — Menu "Formulir" (baru)

```
Formulir (dashboard_menu_id 5)       →  form Konsultasi
└── Konsultasi (dashboard_menu_sub_id 7)  →  slug form konsultasi  (id_dash_menu = "5.7")
```

- `dashboard_menu` `5 = Formulir`; `dashboard_menu_sub` `7 = Konsultasi` (`dashboard_menu_id=5`); **tanpa** extra.
- Nama sub menu = slug form = **`Konsultasi`** (aturan `EmrDashboardController`: sub menu dipakai untuk membentuk `form_name`).
- `form_id = 8`, `nama_form = "Konsultasi"`, `slug = konsultasi`, `id_dash_menu = "5.7"`, `rj=1 ri=1 igd=1 mcu=0`.

Contoh tampilan pohon dashboard pasien setelah fitur:

```
| Catatan Medis | Pengkajian | Order | Formulir |
                        └─ Konsultasi  → /emr/form/konsultasi/{registrasi_detail_id}
```

---

## 4. Bagian Rehabilitasi Medik

### 4.1 `referensi_bagian` — sudah ada `6 = PENUNJANG MEDIS` (tidak perlu ubah)

### 4.2 `bagian` — append 1 baris di akhir `BagianSeeder` (`$penunjangs`)

| bagian_id | nama_bagian | referensi_bagian_id |
|---|---|---|
| **38** | **INSTALASI REHABILITASI MEDIK** | 6 |

> **Catatan:** sudah ada `POLI REHABILITASI MEDIK` (`referensi_bagian_id=1`, RAWAT JALAN) di data existing — itu adalah **poli konsultasi pasien langsung**, BUKAN penunjang. Konsultasi Rehabilitasi Medik pada fitur ini menunjuk ke bagian penunjang `INSTALASI REHABILITASI MEDIK`. Keduanya tidak boleh digabung.
>
> **SELALU append di akhir** list bagian (jangan sisipkan di tengah) agar `bagian_id` existing tidak bergeser & merusak referensi (`registrasi_detail.bagian_id`, `pegawai.bagian_id`, jadwal, dst.).

Jika nanti ada lebih dari satu bagian rehab (mis. Klinik Fisioterapi), daftar bagian rehab difilter `referensi_bagian_id=6` **dan** `nama_bagian LIKE '%REHABILITASI%'` (lihat 7.3).

---

## 5. Master Opsi (SelectOption) & Objek EMR

### 5.1 `SelectOption` baru: `jenis_konsultasi`

Tambahkan key di `App\Helpers\SelectOption::all()`:

```php
'jenis_konsultasi' => [
    'REHABILITASI_MEDIK' => 'Konsultasi Rehabilitasi Medik',
    'KONSUL_LAYANAN'     => 'Konsul Layanan Klinik',
    'RENCANA_KONTROL'    => 'Rencana Kontrol Rawat Jalan',
],
```

Dipakai di view EMR via `\App\Helpers\SelectOption::render('jenis_konsultasi', ...)`.

### 5.2 Objek baru (id 83–88, diteruskan ke `EmrMasterSeeder::$objeks`)

| objek_id | nama_objek | variabel form |
|---|---|---|
| 83 | Jenis Konsultasi | `jenis_konsultasi` |
| 84 | Bagian Tujuan Konsultasi | `bagian_tujuan_id` |
| 85 | Dokter Tujuan Konsultasi | `dokter_tujuan_id` |
| 86 | Tanggal Kontrol | `tanggal_kontrol` |
| 87 | Indikasi Konsultasi | `indikasi_konsultasi` |
| 88 | Bagian Rehabilitasi Medik | `bagian_rehab_id` |

(variabel `catatan` boleh reuse objek 77 "Keterangan".)

### 5.3 Mapping `objek_form_control` form 8

```php
// EmrMasterSeeder::$mapping
8 => [
    'jenis_konsultasi'   => 83,
    'bagian_tujuan_id'   => 84,   // Konsul Layanan (wajib) / Rencana Kontrol (wajib) → bagian Poli (referensi 1)
    'dokter_tujuan_id'   => 85,   // Konsul Layanan (WAJIB) / Rencana Kontrol (wajib) → pegawai dokter
    'tanggal_kontrol'    => 86,   // Rencana Kontrol (wajib)
    'indikasi_konsultasi'=> 87,   // semua jenis
    'bagian_rehab_id'    => 88,   // Konsultasi Rehabilitasi Medik (wajib) → bagian penunjang rehab (dropdown)
],
```

---

## 6. Form EMR "Konsultasi" (Formulir → Konsultasi)

### 6.1 Controller

`App\Http\Controllers\EMR\Konsultasi\KonsultasiController` — di-resolve otomatis oleh `DynamicFormController` pola `Str::studly('konsultasi')`. Method:

- `index($registrasi_detail_id, $emr_id = null)` — render form + riwayat (via `EmrHelper::emrList(formId=8, registrasi_id)`, pola `SoapController`).
- `store(Request $request, $registrasi_detail_id)` — simpan EMR dengan validasi per jenis, **lalu** terbitkan target: konsul rehab → `registrasi_detail` rehab (bagian pilihan); konsul layanan → `registrasi_detail` poli + doctor tujuan (diwajibkan); rencana kontrol → **hanya simpan EMR**, tanpa registrasi baru.
- `update(Request $request, $registrasi_detail_id, $emr_id)` — update EMR (tidak mengubah `registrasi_detail` yang sudah terbit, hanya mengizinkan update catatan/indikasi/tanggal kontrol).
- `destroy($registrasi_detail_id, $emr_id)` — `EmrHelper::delete`.
- `print($emr_id)` — cetak **sesuai jenis konsultasi**: `RENCANA_KONTROL` → lembar "Rencana Kontrol Rawat Jalan" (diserahkan ke pasien); `REHABILITASI_MEDIK`/`KONSUL_LAYANAN` → **slip rujukan konsultasi** (kop RS, pasien, bagian/dokter tujuan, indikasi, ttd dokter) sebagai bukti & instruksi ke bagian tujuan.

View: `resources/views/moduls/EMR/Konsultasi/index.blade.php` (pakai komponen `x-informasi-pasien` bila perlu; pola `moduls/EMR/Soap/index.blade.php`).

### 6.2 Field form (tampil dinamis berdasarkan `jenis_konsultasi`)

| Field | Jenis yang memakai | Kontrol |
|---|---|---|
| `jenis_konsultasi` | semua | `<select>` — `SelectOption::render('jenis_konsultasi')` |
| `bagian_rehab_id` | Rehabilitasi Medik (**wajib**) | `<select>` bagian penunjang rehab (`referensi_bagian_id=6` + `LIKE '%REHABILITASI%'`), default yang pertama |
| `bagian_tujuan_id` | Konsul Layanan (**wajib**) / Rencana Kontrol (**wajib**) | `<select>` Poliklinik = `bagian` `referensi_bagian_id=1` aktif |
| `dokter_tujuan_id` | Konsul Layanan (**wajib**) / Rencana Kontrol (**wajib**) | `<select class="select2-dokter">` pegawai profesi=1 aktif (filter by poli via jadwal; WAJIB untuk Konsul Layanan agar muncul di List Pasien Dokter) |
| `tanggal_kontrol` | Rencana Kontrol (**wajib**) | `<input type="date">` |
| `indikasi_konsultasi` | semua | `<textarea>` |
| `catatan` (opsional) | semua | `<textarea>` |

JS toggle (`@push('scripts')`): pilih Rehabilitasi Medik → tampilkan `#block-rehab`, sembunyikan poli/dokter/kontrol; pilih Konsul Layanan → tampilkan poli+dokter; pilih Rencana Kontrol → tampilkan poli+dokter+`tanggal_kontrol`.

Validasi `store()` (disesuaikan per jenis):

| jenis_konsultasi | Wajib diisi |
|---|---|
| `REHABILITASI_MEDIK` | `bagian_rehab_id`, `indikasi_konsultasi` |
| `KONSUL_LAYANAN` | `bagian_tujuan_id`, `dokter_tujuan_id`, `indikasi_konsultasi` |
| `RENCANA_KONTROL` | `bagian_tujuan_id`, `dokter_tujuan_id`, `tanggal_kontrol`, `indikasi_konsultasi` |

### 6.3 Akses EHR

`akses_ehr` form 8:

| profesi_id | profesi | akses |
|---|---|---|
| 1 | Dokter | create/read/update/delete |
| 2 | Perawat | read |
| *(opsional, baru)* | Dokter/terapis Rehabilitasi Medik | read + create (untuk mencatat tindak lanjut) |

---

## 7. Alur Kerja

### 7.1 Konsultasi Rehabilitasi Medik

```
Dashboard Pasien → Formulir → Konsultasi → jenis: Konsultasi Rehabilitasi Medik
      │  indikasi + bagian_rehab_id (dropdown bagian rehab, default INSTALASI REHABILITASI MEDIK)
      ▼
store() dalam satu transaksi:
      ├─ 1. EmrHelper::insert(form 8, data, registrasi_detail_id ASAL)   → emr + emr_detail
      ├─ 2. CREATE registrasi_detail TUJUAN:
      │      registrasi_id = SAMA (masih satu kunjungan), bagian_id = bagian_rehab_id,
      │      terima_dari = 'INTERNAL', tgl_daftar = now,
      │      kelas_id/hak_kelas_id snapshot dari registrasi_detail ASAL, status_batal=0
      └─ 3. (opsional/iterasi 2) BillTemp untuk detail tujuan
      ▼
Penunjang Medis → Rehabilitasi Medik → Daftar Pasien Rehabilitasi Medik
      └─ pasien muncul (registrasi_detail dengan bagian rehab terpilih) → buka Dashboard Pasien
```

### 7.2 Konsul Layanan Klinik (konsul internal Poli)

```
Dashboard Pasien → Formulir → Konsultasi → jenis: Konsul Layanan Klinik
      │  poli tujuan + dokter tujuan (WAJIB) + indikasi
      ▼
store() dalam satu transaksi (validasi: dokter_tujuan_id wajib):
      ├─ 1. EmrHelper::insert(form 8, data, registrasi_detail_id ASAL)
      ├─ 2. CREATE registrasi_detail TUJUAN: registrasi_id SAMA,
      │      bagian_id = bagian_tujuan_id (Poli), terima_dari='INTERNAL', tgl_daftar=now
      ├─ 3. CREATE bill_temp (registrasi_detail_id baru, snapshot kelas/nasabah seperti DaftarRajal)
      ├─ 4. CREATE registrasi_urut (pegawai_id = dokter tujuan, bagian_id = poli tujuan,
      │      urutan = GenerateHelper::generateNoUrut(dokter, poli, hari), tgl_urut = estimasi)
      └─ 5. CREATE penanggung_rawat (registrasi_id, rawat_user_id = user_id dokter tujuan)
      ▼
Rawat Jalan → List Pasien Dokter (filter poli & rawat_user dokter konsulen)
      └─ JOIN registrasi_detail + bill_temp + registrasi_urut + penanggung_rawat
         → pasien konsul muncul otomatis (masih SATU registrasi)
```

> Kenapa bill_temp/registrasi_urut/penanggung_rawat? Query `ListPasienDokterController` melakukan JOIN keras ke kelima tabel itu — tanpa baris pendukung, pasien konsul tidak akan muncul di list dokter. Jumlah urutan dokter konsulen dihitung ulang dari jadwal (dua pasien "rawat jalan murni" & "konsul" saling antri dengan urutan konsisten).

### 7.3 Daftar Pasien Rehabilitasi Medik (sub menu baru)

Controller `DaftarPasienRehabilitasiMedikController`:

- `index()`: filter **bagian rehab aktif (session**, pola `lab_bagian_id`/`rad_bagian_id`), `tanggal_konsul` (tgl_daftar), kata kunci pasien/no MR. Query utama:

```php
DB::table('registrasi_detail as rd')
    ->join('registrasi as r', 'r.registrasi_id', '=', 'rd.registrasi_id')
    ->join('pasien as p', 'p.pasien_id', '=', 'r.pasien_id')
    ->join('bagian as b', 'b.bagian_id', '=', 'rd.bagian_id')
    ->where('rd.bagian_id', $bagianRehabId)
    // + status_batal != 1 OR NULL di rd/r/p/b
    // + filter emr form konsultasi (form_id 8) utk menandai konsul rehab
```

- Kolom: No MR, nama pasien, tanggal konsul, poli/ruang asal (`rd.bagian_asal_id` / via emr `bagian_rehab_id`), indikasi (dari `emr_detail` variabel `indikasi_konsultasi`), aksi **Buka Dashboard Pasien** → `route('dashboard_pasien.index', $rd->registrasi_detail_id)`.
- `pilihBagian(Request)`: simpan `session('rehab_bagian_id')` — bagian rehab (`referensi_bagian_id=6` + `LIKE '%REHABILITASI%'`). Route manual (non-CRUD) seperti lab/rad:

```php
Route::post('/daftar_pasien_rehabilitasi_medik/pilih-bagian',
    [DaftarPasienRehabilitasiMedikController::class, 'pilihBagian'])
    ->name('daftar_pasien_rehabilitasi_medik.pilih_bagian');
```

- Status/keterangan konsul dibaca live dari `emr_detail` (`EmrHelper::emrDetailByVariabel`), tidak perlu kolom baru.

### 7.4 Rencana Kontrol Rawat Jalan

```
Dashboard Pasien → Formulir → Konsultasi → jenis: Rencana Kontrol Rawat Jalan
      │  poli tujuan + dokter tujuan + tanggal_kontrol + indikasi
      ▼
store():
      └─ 1. EmrHelper::insert(form 8, data, registrasi_detail_id ASAL)
             → variabel jenis_konsultasi=RENCANA_KONTROL, bagian_tujuan_id,
               dokter_tujuan_id, tanggal_kontrol, indikasi_konsultasi
      TIDAK membuat registrasi_detail / registrasi baru.
      ▼
      2. (tombol Cetak) GET /emr/konsultasi/print/{emr_id}
      └─ lembar "Rencana Kontrol Rawat Jalan" (kop RS, identitas pasien,
         poli & dokter tujuan, tanggal kontrol, indikasi, ttd dokter & pasien)
         → dicetak & diserahkan ke pasien
```

- **Tidak ada `registrasi`/`registrasi_detail` baru** — pasien belum "terdaftar". Jika pasien datang pada tanggal kontrol, petugas mendaftarkan lewat **Daftar Rawat Jalan** normal (registrasi baru dibuat saat itu juga). `tanggal_kontrol` di EMR cukup sebagai pengingat/rujukan cetak.
- **Edisi/update** diperbolehkan selama belum dicetak/ditinjau; tombol Cetak memakai `AksesEhr::can(form 8, 'read')`.
- **Tombol Cetak tersedia untuk KETIGA jenis** — `print()` membedakan jenis konsultasi: Rencana Kontrol → lembar rencana kontrol (untuk pasien); Konsul Rehab & Konsul Layanan → slip rujukan konsultasi (bukti/instruksi ke bagian tujuan).

---

## 8. Integrasi EMR / Dashboard Pasien (ringkasan perubahan tabel `form`)

| form_id | nama_form | slug | id_dash_menu | rj/ri/igd |
|---|---|---|---|---|
| **8** | Konsultasi | `konsultasi` | `'5.7'` | 1/1/1 |

- `dashboard_menu` **baru** `5 = Formulir`.
- `dashboard_menu_sub` **baru** `7 = Konsultasi` (`dashboard_menu_id=5`), **tanpa extra** → `header_ehr` (LEFT JOIN) otomatis menghasilkan baris `5.7`.
- `form.ri/rj/igd = 1` → form tampil di dashboard pasien RJ/RI/IGD.
- Gate: `AksesEhr::can(form 8, ...)` di controller (`index` read / `store` create / `update` update / `destroy` delete) + `$aksesCrud` diteruskan ke view (tombol Baru/Simpan/Hapus sesuai hak), pola `SoapController`.

---

## 9. Seeding — Ringkasan Perubahan

| Seeder | Perubahan |
|---|---|
| `BagianSeeder` | append `INSTALASI REHABILITASI MEDIK` (id 38, `referensi_bagian_id=6`) |
| `ModulMenuSubMenuSeeder` | menu 16 "Rehabilitasi Medik" (modul 8) + sub 47 `PenunjangMedis/RehabilitasiMedik/DaftarPasienRehabilitasiMedik/daftar_pasien_rehabilitasi_medik` → lalu jalankan `php artisan seeder:sync-master-menu` |
| `EmrMasterSeeder` | `dashboard_menu` 5 "Formulir", `dashboard_menu_sub` 7 "Konsultasi"; form 8; objek 83–88; mapping form 8; `backfillObjekId(8)`; akses_ehr form 8 (Dokter full, Perawat read) |
| `SelectOption` (helper) | key `jenis_konsultasi` |
| `MasterPegawaiSeeder` (opsional) | profesi/pegawai Rehab Medik untuk user & akses sidebar |
| `UserSeeder` | admin otomatis mendapat sub 47 (dinamis); tambah user terapis rehab bila perlu |

`DatabaseSeeder`/`GenerateHelper::resetSequence()`: **tidak ada tabel baru** → daftar reset tidak berubah (objek/objek_form_control sudah terdaftar).

---

## 10. Route yang Ditulis Manual (routes/web.php)

Hanya aksi non-CRUD (auto-router mengurus CRUD sub_menu):

```php
// Rute manual penunjang rehab (pola lab/rad)
Route::post('/daftar_pasien_rehabilitasi_medik/pilih-bagian',
    [DaftarPasienRehabilitasiMedikController::class, 'pilihBagian'])
    ->name('daftar_pasien_rehabilitasi_medik.pilih_bagian');

// Cetak slip konsultasi (EMR Konsultasi): lembar rencana kontrol utk pasien,
// slip rujukan utk Konsul Rehab & Konsul Layanan — dibedakan per jenis_konsultasi
Route::get('/emr/konsultasi/print/{emr_id}',
    [KonsultasiController::class, 'print'])
    ->name('emr.konsultasi.print');

// Konsultasi EMR TIDAK perlu route manual untuk index/store/update/destroy:
// DynamicFormController sudah punya /emr/form/{form_name}/... generic.
```

---

## 11. Risiko, Konsekuensi & Keputusan yang Harus Disepakati

1. **Konsul layanan poli memerlukan 4 baris pendukung** (`bill_temp`, `registrasi_urut`, `penanggung_rawat`, `registrasi_detail`) agar muncul di List Pasien Dokter — karena query-nya JOIN keras ke semua tabel tersebut. Jangan terbitkan `registrasi_detail` sendirian.
2. **Antrian/konsul = SAMSKA `generateNoUrut` + `hitungEstimasi`** — urutan pasien konsul dihitung dari jadwal dokter konsulen di hari yang sama (bisa menempel di antrian existing). Disepakati apakah konsul dapat nomor urut sendiri.
3. **Rencana kontrol TIDAK menerbitkan registrasi** — hanya EMR rencana kontrol (bisa dicetak). Pasien tetap didaftarkan lewat **Daftar Rawat Jalan** saat datang ulang; EMR ini tidak boleh otomatis menciptakan antrian.
4. **Dua jenis konsul & rehab = satu `registrasi`, banyak `registrasi_detail`** — pastikan billing (`bill_temp`) tidak dihitung dobel untuk detail asal+tujuan; `BillTemp` untuk konsul ditujukan ke detail tujuan (iterasi billing).
5. **`id_dash_menu = '5.7'` harus cocok dengan id aktual `dashboard_menu`** — jangan hardcode id di luar `EmrMasterSeeder` (idempotent `updateOrInsert`).
6. **Bagian rehab wajib `referensi_bagian_id=6`** — filter Daftar Pasien & dropdown konsul memakai ini + `LIKE '%REHABILITASI%'`; jangan `whereNull`.
7. **Akronim folder** — `RehabilitasiMedik`, `DaftarPasienRehabilitasiMedik`, `Konsultasi` kata utuh (bukan `Rehab`) agar basename/URI konsisten.
8. **Objek/variabel baru** memakai objek id **di luar rentang 1–82** yang sudah dipakai (83–88).
9. **`form` id 8 & sub_menu 47 & menu 16 harus bebas** — pastikan tidak dipakai fitur lain sebelum migrate/seed.

---

## 12. Rencana Implementasi Bertahap

### Iterasi 1 (inti)
1. `SelectOption::all()` + `jenis_konsultasi`.
2. `EmrMasterSeeder`: dashboard menu 5 "Formulir"/sub 7 "Konsultasi", form 8, objek 83–88, mapping, akses_ehr.
3. `BagianSeeder`: append INSTALASI REHABILITASI MEDIK.
4. `KonsultasiController` (EMR) + view `moduls/EMR/Konsultasi/index.blade.php` + logika terbitkan `registrasi_detail` pada `store` (hanya untuk jenis Rehab & Konsul Layanan); Rencana Kontrol cukup simpan EMR.
5. Cetak slip (ketiganya): view `moduls/EMR/Konsultasi/print.blade.php` (lembar rencana kontrol untuk pasien / slip rujukan konsul) + route `emr.konsultasi.print`.
6. `DaftarPasienRehabilitasiMedikController` (Penunjang Medis) + view index/detail + route `pilih_bagian`.
7. `ModulMenuSubMenuSeeder` (menu 16 + sub 47) + `seeder:sync-master-menu` + clear sidebar cache + `route:clear`/`cache:clear`.
8. `MasterPegawaiSeeder`/`UserSeeder` opsional (user terapis/pegawai rehab).

### Iterasi 2 (penguatan)
- `BillTemp` linkage resmi untuk detail konsul; snapshot biaya konsultasi/tarif.
- Aksi "Selesaikan / Tindak Lanjut" konsul di Daftar Pasien Rehabilitasi Medik (status konsul).
- Print/slip rujukan konsultasi; notifikasi ke bagian tujuan.

---

## 13. Referensi Kode Existing yang Dipakai

- `App\Helpers\EmrHelper` — `insert/update/delete`, `formBySlug('konsultasi')`, `emrList`, `emrDetailByVariabel`.
- `App\Helpers\AksesEhr` — gate CRUD form 8.
- `App\Helpers\GenerateHelper` — `generateNoUrut`, `hitungEstimasi`.
- `App\Helpers\SelectOption` — `jenis_konsultasi` baru.
- `App\Http\Controllers\EMR\Soap\SoapController` — pola controller EMR + redirect `emr.dynamic.index`.
- `App\Http\Controllers\Registrasi\Pendaftaran\DaftarRajal\DaftarRajalController` — pola pembuatan `registrasi` + `registrasi_detail` + `bill_temp` + `registrasi_urut` + `penanggung_rawat` (dipakai Konsul Layanan & referensi pasien daftar ulang pada tanggal kontrol).
- `App\Http\Controllers\RawatJalan\Pasien\ListPasienDokter\ListPasienDokterController` — query yang menentukan syarat munculnya pasien konsul layanan poli.
- `App\Http\Controllers\PenunjangMedis\Laboratorium\DaftarPesananLaboratorium\DaftarPesananLaboratoriumController` — pola filter bagian session + list penunjang.
- `EmrMasterSeeder` / `ModulMenuSubMenuSeeder` + `seeder:sync-master-menu` — seed modul/menu/sub_menu & dashboard/form/akses.