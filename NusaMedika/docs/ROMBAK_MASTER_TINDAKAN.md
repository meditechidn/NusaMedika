# ROMBAK MASTER TINDAKAN (+ Master Kategori, Master Group Tindakan, Master Tarif)

> Status: **Sudah diimplementasikan** — migration & kode sudah masuk, DB sudah di-`migrate:fresh --seed`.
> Dokumen ini adalah acuan implementasi hasil consecutivo rombak fitur **Master Tindakan**. Semua nama (tabel, folder, route, slug) merupakan keputusan yang sudah disepakati dan harus dipakai apa adanya agar rule auto-route `sub_menu` tetap berlaku.

---

## 1. Ringkasan

Master Tindakan saat ini terlalu gemuk: satu form memuat `kode_tindakan`, `nama_tindakan`, `bagian_id`, `kategori`, `satuan_hasil`, `nilai_normal`, `kode_bpjs`, `kode_inacbg`, `kode_loinc`, `keterangan`, **plus** seluruh blok tarif per kelas.

Rombak ini memecahnya jadi **empat master terpisah**:

| Master | Isi | Alasan pemisahan |
|---|---|---|
| **Master Tindakan** | `kode_tindakan`, `nama_tindakan`, `kategori_tindakan_id` | Master ini hanya katalog. Tidak ada kolom governorship/lisensi/hasil, tidak ada harga. |
| **Master Kategori Tindakan** | `nama_kategori_tindakan` | Kategori sebelumnya enum-ish (`LAB`/`RAD`/`LAIN`) di dalam controller. Sekarang jadi data master + CRUD. |
| **Master Group Tindakan** | `nama_group_tindakan` + `bagian_id` + daftar tindakan | Ini yang **menggantikan `tindakan.bagian_id`**. Satu group = satu panel pemeriksaan milik satu unit, berisi banyak tindakan. |
| **Master Tarif** | tarif per tindakan × kelas perawatan | Harga tidak lagi ikut form Master Tindakan./master tarif punya lifecycle & aturan sendiri. |

Sisa kolom yang dihapus (`satuan_hasil`, `nilai_normal`, `kode_bpjs`, `kode_inacbg`, `kode_loinc`, `keterangan`) **tidak dipindah ke master manapun** — `satuan_hasil` & `nilai_normal` justru diisi **manual per baris** di form Order Laboratorium, sedangkan kode BPJS/INACBG/LOINC postponable (bridging VClaim/INACBG) dan akan muncul sebagai master tersendiri nanti.

### 1.1 Keputusan yang sudah dikunci

| Topik | Keputusan |
|---|---|
| Field Master Tindakan | hanya `kode_tindakan` + `nama_tindakan` + `kategori_tindakan_id` |
| `bagian_id` | dihapus dari `tindakan`, dipindah ke `group_tindakan` |
| Kategori | FK integer `kategori_tindakan_id` → tabel `kategori_tindakan` (bukan varchar) |
| Kategori | ada halaman **CRUD + sub_menu baru + seeder baseline** |
| Kolom dihapus di DB | **migration baru** yang `drop` kolom (bukan mengedit migration lama) |
| `satuan_hasil`/`nilai_normal` | snapshot di `order_laboratorium_detail` **tetap**, sumbernya **diisi manual** di form order |
| Tarif | **satu tarif per tindakan per kelas**, ada **baris tarif default** (`kelas_ruang_id` NULL) |
| `tarif_bpjs` | **dihapus** |
| Master Tarif | memakai tabel `tarif` (satu baris per tindakan per kelas + baris default) |
| 1 tindakan di banyak unit | query **distinct, ambil group terkecil** (`group_tindakan_id` min) |
| Penempatan sub_menu baru | ketiganya di **Administrator → Manajemen Master** (menu 9), sejajar Master Kelas / Master ICD |

---

## 2. Migration

Semua migration baru berawalan `2026_10_01_` dan mengikuti konvensi repo: `Schema::create` dengan `increments()` PK, kolom audit `input_time`/`mod_time` (timestamp 6) + `input_user_id`/`mod_user_id`, soft delete `status_batal` (NULL atau 0 = aktif).

### 2.1 `2026_10_01_000001_create_kategori_tindakan_table`

| Kolom | Tipe |
|---|---|
| `kategori_tindakan_id` | `increments` (PK) |
| `nama_kategori_tindakan` | `string(100)` unique |
| `input_time` / `input_user_id` / `mod_time` / `mod_user_id` | audit |
| `status_batal` | `smallInteger` nullable |

### 2.2 `2026_10_01_000002_create_group_tindakan_table`

| Kolom | Tipe |
|---|---|
| `group_tindakan_id` | `increments` (PK) |
| `nama_group_tindakan` | `string(100)` |
| `bagian_id` | `integer` + index |
| audit + `status_batal` | — |

`bagian_id` menunjuk unit penunjang (`bagian.referensi_bagian_id = 6`). Tidak dipasang FK, mengikuti pola tabel lain yang juga tidak memasang FK.

### 2.3 `2026_10_01_000003_create_group_tindakan_tindakan_table`

Pivot group ↔ tindakan.

| Kolom | Tipe |
|---|---|
| `group_tindakan_tindakan_id` | `increments` (PK) |
| `group_tindakan_id` | `integer` |
| `tindakan_id` | `integer` |
| audit + `status_batal` | — |

Plus `unique(['group_tindakan_id', 'tindakan_id'])` dan `index('tindakan_id')`.

### 2.4 `2026_10_01_000004_rombak_tindakan_table`

- **Drop**: `bagian_id`, `kategori` (varchar 20), `satuan_hasil`, `nilai_normal`, `kode_bpjs`, `kode_inacbg`, `kode_loinc`, `keterangan`.
- **Add**: `kategori_tindakan_id` `integer` nullable + `index('kategori_tindakan_id')`.
- `down()` = kebalikan persis.

Kolom yang tersisa: `tindakan_id`, audit, `status_batal`, `kode_tindakan` (unique, 20), `nama_tindakan` (255), `kategori_tindakan_id`.

### 2.5 `2026_10_01_000005_drop_tarif_bpjs_from_tindakan_harga_table`

- **Drop**: `tindakan_harga.tarif_bpjs`.
- Struktur `tindakan_harga` sesudahnya: `tindakan_harga_id`, audit, `status_batal`, `tindakan_id`, `kelas_ruang_id` (NULL = default), `tarif` decimal(12,2).
- `down()` = tambah kolom `tarif_bpjs` kembali.

### 2.6 Yang sengaja TIDAK disentuh

- `order_laboratorium_detail` & `order_radiologi_detail` — tidak ada migration. Kolom `satuan_hasil` / `nilai_normal` / `harga` tetap seperti sekarang.
- `suket_mcu.harga` — tidak terkait.

---

## 3. Model

### 3.1 `app/Models/Tindakan.php` (diubah)

```php
protected $fillable = ['kode_tindakan', 'nama_tindakan', 'kategori_tindakan_id'];

public function kategori(): BelongsTo   // ke KategoriTindakan
public function groups(): BelongsToMany // ke GroupTindakan via pivot group_tindakan_tindakan
```

- Const `KATEGORI` (`LAB`/`RAD`/`LAIN`) **dihapus**.
- Accessor `kategori_label` **dihapus** (label kini dari relasi `kategori->nama_kategori_tindakan`).
- Scope `aktif()` tetap.

### 3.2 `app/Models/KategoriTindakan.php` (baru)

- `$table = 'kategori_tindakan'`, `$primaryKey = 'kategori_tindakan_id'`, `$timestamps = false`.
- `$fillable = ['nama_kategori_tindakan']`.
- Scope `aktif()` (`status_batal` NULL atau 0).
- `tindakan(): HasMany` → `Tindakan`.

### 3.3 `app/Models/GroupTindakan.php` (baru)

- `$table = 'group_tindakan'`, `$primaryKey = 'group_tindakan_id'`, `$timestamps = false`.
- `$fillable = ['nama_group_tindakan', 'bagian_id']`.
- Scope `aktif()`.
- `bagian(): BelongsTo` → `Bagian`.
- `tindakan(): BelongsToMany` → `Tindakan` via pivot `group_tindakan_tindakan`, dengan `->wherePivot(fn => NULL/0)` dan parent `aktif()`.

### 3.4 `app/Models/TindakanHarga.php` (diubah)

- `$fillable` → `['tindakan_id', 'kelas_ruang_id', 'tarif']` (hapus `tarif_bpjs`).
- Relasi & scope tidak berubah.

---

## 4. Sub_menu baru

Tiga halaman CRUD baru, semuanya di **Administrator → Manajemen Master (`menu_id = 9`)**, level yang sama dengan *Master Kelas* dan *Master ICD*. Dibuat lewat `php artisan make:submenu`, lalu record `sub_menu` di-insert dan `seeder:sync-master-menu` dijalankan (jangan edit `ModulMenuSubMenuSeeder.php` manual).

| Nama sub_menu | `file_sub_menu` | Route |
|---|---|---|
| Master Kategori Tindakan | `Administrator/ManajemenMaster/MasterKategoriTindakan/master_kategori_tindakan` | `admin.master_kategori_tindakan.*` |
| Master Group Tindakan | `Administrator/ManajemenMaster/MasterGroupTindakan/master_group_tindakan` | `admin.master_group_tindakan.*` |
| Master Tarif | `Administrator/ManajemenMaster/MasterTarif/master_tarif` | `admin.master_tarif.*` |

Folder view = folder controller (harus identik), basename blade = nama file blade = segment terakhir `file_sub_menu`.

### 4.1 Master Kategori Tindakan

CRUD biasa satu kolom. Mengikuti pola `KelasController` persis: `index`/`create`/`store`/`edit`/`update`/`destroy`, assign manual (`new Model; assign; save()`) di dalam `DB::beginTransaction()`, filter `aktif()`, dan hapus soft-delete.

### 4.2 Master Group Tindakan

Field:

- `nama_group_tindakan` (wajib) — contoh: `DARAH LENGKAP`, `RONTGEN THORAX`.
- `bagian_id` (wajib) — dropdown unit penunjang (`Bagian::aktif()->where('referensi_bagian_id', 6)`).
- `tindakan_ids[]` (wajib) — `<select multiple class="select2">` berisi `Tindakan::aktif()` (kode + nama).

Sync pivot memakai pola `syncBarangMappings()` yang sudah ada di `SupplierController`: soft-delete pivot aktif lama → insert ulang. Kolom index menampilkan badge "N Tindakan".

### 4.3 Master Tarif

Master ini mengelola tabel `tarif` (`2026_10_31_000001`, menggantikan `tindakan_harga`), satu baris per (tindakan, kelas) + baris default.

- `index()` — daftar tindakan (kode, nama, kategori) + tarif default + jumlah kelas terisi + tombol "Atur Tarif". Kolom "Tarif Default" boleh link ke `edit`.
- `create()` — `?tindakan_id=` wajib; form untuk **satu** tindakan.
- `edit()` — `?tindakan_id=`; form sama, terisi.
- `store()` / `update()` — `update()` melakukan **soft-delete semua baris aktif lalu insert ulang** (pola `syncHarga()` sekarang; ini yang membuat unique `['tindakan_id','kelas_ruang_id']` tetap aman).
- `destroy($master_tarif)` — soft-delete **seluruh** tarif aktif tindakan (parameter = `tindakan_id`).

Form tarif (dipindah apa adanya dari `_form.blade.php` Master Tindakan):

| Baris | Field | Aturan |
|---|---|---|
| **Tarif Default** | `tarif[default]` | wajib; `kelas_ruang_id` NULL |
| tiap `KelasRuang::aktif()` | `tarif[{kelas_ruang_id}]` | opsional; kosong = pakai default |
| tiap `KelasRuang::aktif()` | `tarif[{kelas_ruang_id}]` | **dihapus** — `tarif_bpjs` tidak lagi ada |

Kelas yang tidak diisi tidak di-insert, sehingga `PenunjangHelper::tarif()` jatuh ke tarif default — perilaku saat ini dipertahankan persis.

---

## 5. Rombak Master Tindakan

### 5.1 `MasterTindakanController`

| Method | Perubahan |
|---|---|
| `index()` | filter `search` (kode/nama) + `kategori_tindakan_id`. **Hapus** filter `bagian_id` dan `with('bagian')`, ganti `with('kategori')`. Kolom kategori di query dari master. |
| `validated()` | `kode_tindakan` required\|string\|max:20 **\| unique:tindakan,kode_tindakan** (dulu hanyaunique index DB, sekarang juga divalidasi supaya pesan error jelas) · `nama_tindakan` required\|string\|max:255 · `kategori_tindakan_id` required\|integer\|exists. **Hapus** 8 field lama. |
| `store()` / `update()` | sisa 3 assignment saja. **Hapus** pemanggilan `syncHarga()`. |
| `syncHarga()` | **dihapus** — pindah ke `MasterTarifController`. |
| `destroy()` | soft-delete `TindakanHarga` **dan** soft-delete pivot `group_tindakan_tindakan` milik tindakan tsb. |
| `formData()` | **Hapus** `bagianList` & `kelasList`; tambah `kategoriList = KategoriTindakan::aktif()->orderBy('nama_kategori_tindakan')->get()`. |

`array_merge` default pada `validated()` tetap dipakai untuk kolom opsional (`kategori_tindakan_id` wajib, jadi tidak perlu default — tapi tetap aman bila `array_merge` dipakai).

### 5.2 `_form.blade.php`

Sisa form hanya tiga field: Kode Tindakan, Nama Tindakan, Kategori (dropdown dari `$kategoriList`). Ditambah satu baris catatan berbahasa Indonesia: *"Tarif setiap tindakan diatur di Master Tarif."* Seluruh blok tarif (sekitar baris 135–181 pada versi lama) dihapus.

### 5.3 `master_tindakan.blade.php`

- **Hapus** kolom: Bagian, Satuan & Nilai Normal, Tarif Default.
- Kolom Kategori: badge dari `$t->kategori?->nama_kategori_tindakan`.
- Filter kategori: iterasi `KategoriTindakan::aktif()` bukan `Tindakan::KATEGORI`.
- Filter `bagian_id` dihapus.

---

## 6. Dampak ke Order Laboratorium & Radiologi

Kolom `bagian_id` dihapus, sehingga sumber kebenaran "tindakan ini milik unit mana" berubah dari `tindakan.bagian_id` menjadi **group tindakan**.

### 6.1 `PenunjangHelper`

**`tarif(int $tindakanId, ?int $kelasRuangId): array`**

- Return type menjadi `array{kelas_ruang_id: ?int, tarif: float}` — key `tarif_bpjs` **dihapus**.
- Logika lookup tidak berubah (kelas spesifik → fallback `kelas_ruang_id IS NULL`).

**`insertDetails()`**

- Hapus baris yang menyalin `$tindakan->satuan_hasil` / `$tindakan->nilai_normal`.
- Ganti: `$detail['satuan_hasil'] = $item['satuan_hasil'] ?? null;` dan `$detail['nilai_normal'] = $item['nilai_normal'] ?? null;` (hanya untuk `$jenis === 'lab'`).

### 6.2 `LaboratoriumController` & `RadiologiController`

Tambah private helper:

```php
/** Map tindakan_id => bagian_id hasil group (group terkecil). */
private function tindakanBagianMap(): array
```

Query: join `group_tindakan_tindakan` → `group_tindakan`, filter pivot & group `aktif()`, `groupBy('tindakan_id')->selectRaw('MIN(group_tindakan_id)')`, lalu join ke `group_tindakan` untuk ambil `bagian_id`. Hasil dipakai untuk:

- **index()** — dikirim ke view sebagai peta `tindakan_id => bagian_id`.
- **validatedItems()** — cek validitas diganti dari
  `if ((int) $tindakan->bagian_id !== $bagianTujuanId)` menjadi
  `if (($bagianMap[$tindakanId] ?? null) !== $bagianTujuanId)` dengan pesan
  `"{$nama}" tidak termasuk dalam Group Tindakan bagian tujuan terpilih.`

`validatedItems()` juga membaca input satuan/nilai normal per baris (lihat 6.3).

> Implementasi: peta group **berada di `PenunjangHelper::tindakanBagianMap()`**, bukan
> method private per controller, karena dipakai identik oleh Lab & Rad.

### 6.3 Sinkronisasi indeks baris (poin rawan)

Baris item di form Order Lab bisa dihapus user kapan saja, sehingga `tindakan_id[]`, `satuan_hasil[]`, `nilai_normal[]` **tidak boleh**zorip `[]` biasa — indeks akan bergeser.

Solusi: pakai **kunci indeks per baris**, bukan `[]`:

```html
<input name="satuan_hasil[{{ $counter }}]" ...>
<input name="nilai_normal[{{ $counter }}]" ...>
```

Counter = `tr.dataset.item` yang sudah dipakai script. Ketiga field memakai indeks yang sama, termasuk `tindakan_id[{{ $counter }}]` (bukan `tindakan_id[]`), sehingga controller cukup `foreach ($tindakanIds as $baris => $tindakanId)` lalu ambil `satuan_hasil[$baris]` / `nilai_normal[$baris]`. Baris yang dihapus tidak lagi menggeser apa pun.[

### 6.4 `Laboratorium/index.blade.php`

- Opsi `<option>`: `data-bagian` diisi dari peta group, bukan `$tk->bagian_id`; **hapus** `data-satuan` & `data-nilai-normal`.
- Fungsi JS `filterOptions()` (yang hanya membaca `data-bagian`) **tetap**; fungsi `renderMeta()` yang membaca `data-satuan` **dihapus**.
- Dua kolom span meta (Satuan / Nilai Normal) diganti jadi `<input>` per baris dengan nama berindeks (6.3). Pada mode view, input di-`readonly`.
- `existingItemsJson` ditambah key `satuan` & `nilai_normal` dari `$editDetails` agar form edit ter-prefill.
- `<th>` kolom disesuaikan labelnya (mis. "Satuan Hasil (input)" / "Nilai Normal (input)").

### 6.5 `Radiologi/index.blade.php`

Tabel detail radiologi **tidak punya** kolom satuan/nilai normal. Yang berubah hanya sumber `data-bagian` → peta group.

---

## 7. Seeder

### 7.1 `KategoriTindakanSeeder` (baru)

Baseline sesuai nilai lama: **Laboratorium**, **Radiologi**, **Lainnya**. Idempotent via `updateOrInsert` keyed on `nama_kategori_tindakan`.

### 7.2 `TindakanSeeder` (dirombak)

10 tindakan baseline tetap sama (6 LAB, 4 RAD), tetapi:

- lookup unit **per nama** — `Bagian::aktif()->where('nama_bagian', 'INSTALASI LABORATORIUM' / 'INSTALASI RADIOLOGI')->first()` — bukan id hardcode 36/37 (id bisa bergeser karena `BagianSeeder` append-only);
- `kategori_tindakan_id` diisi dari `KategoriTindakan` berdasarkan nama, bukan string `LAB`/`RAD`;
- payload `updateOrInsert` tidak lagi memuat `bagian_id`, `kategori`, `satuan_hasil`, `nilai_normal`, `kode_bpjs`, `kode_inacbg`, `kode_loinc`, `keterangan`;
- **blok tarif dihapus** dari seeder ini (pindah ke `MasterTarifSeeder`).

### 7.3 `GroupTindakanSeeder` (baru)

Contoh sesuai kebutuhan — group sebagai panel pemeriksaan:

| Group | Unit | Tindakan |
|---|---|---|
| `DARAH LENGKAP` | INSTALASI LABORATORIUM | KLEBENG DARAH, LEUKOSIT, ERITROSIT, HEMOGLOBIN, HEMATOKRIT, PLATELET |
| `RONTGEN THORAX` | INSTALASI RADIOLOGI | RONTGEN THORAX |
| `PANEL LABORATORIUM` | INSTALASI LABORATORIUM | sisa tindakan LAB |
| `PENUNJANG RADIOLOGI` | INSTALASI RADIOLOGI | sisa tindakan RAD |

Karena 10 tindakan baseline diolkode satu per satu, pembagian dilakukan per item (satu item = satu group) kecuali yang dikelompokkan eksplisit di tabel pertama.

### 7.4 `MasterTarifSeeder` (baru)

Pengganti blok tarif lama di `TindakanSeeder`:

- tarif default: 50.000 (LAB), 100.000 (RAD);
- per kelas `kelas_ruang_id` 1–5 (Kelas 1/2/3, VIP, VVIP): 75.000 / 65.000 / 50.000 / 100.000 / 120.000.

### 7.5 `DatabaseSeeder`

- Urutan baru: `KategoriTindakanSeeder` → `TindakanSeeder` → `GroupTindakanSeeder` → `MasterTarifSeeder` (ketiganya setelah `JadwalDokterSeeder`, sebelum `EmrMasterSeeder`).
- `resetSequences()` ditambah: `kategori_tindakan`, `group_tindakan`, `group_tindakan_tindakan`.

---

## 8. Verifikasi

```bash
docker compose up -d --force-recreate app vite
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan seeder:sync-master-menu
docker compose exec app php artisan route:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan test
docker compose exec app vendor/bin/pint
```

Cek manual:

1. `/master_tindakan` — form hanya 3 field, tidak ada blok tarif; kolom Kategori & filter kategori berfungsi.
2. `/master_kategori_tindakan` — CRUD 1 kolom jalan.
3. `/master_group_tindakan` — buat group `DARAH LENGKAP` berisi beberapa tindakan, badge "N Tindakan" benar.
4. `/master_tarif` — atur tarif default + per kelas; kolom tarif default di index terbaca.
5. Order Laboratorium dari EMR — dropdown tindakan terfilter group sesuai unit tujuan; satuan & nilai normal bisa diisi manual per baris; harga ter-snapshot dari Master Tarif.
6. Tindakan tanpa group tidak bisa dipilih di unit yang tidak sesuai (pesan error Indonesia).

---

## 9. Di luar scope

- `SelectOption` — tidak ada key kategori tindakan, jadi tidak perlu disentuh.
- Bridging BPJS/INACBG/LOINC — `kode_bpjs`, `kode_inacbg`, `kode_loinc` dihapus, akan kembali sebagai master tersendiri saat bridging VClaim dikerjakan.
- Master Tarif/ Tarif Per Periode (tarif efektif per tanggal) — tidak masuk scope; sekarang tetap satu tarif aktif per tindakan per kelas.

---

## 10. Catatan implementasi (hasil verifikasi)

Sudah dijalankan & diverifikasi lewat HTTP (`migrate:fresh --seed` + curl session login):

| Uji | Hasil |
|---|---|
| 24 route auto (4 master × 6) | terdaftar, `master_tindakan` URI tetap |
| 12 halaman index/create/edit | HTTP 200 |
| Simpan & ubah master (kategori, tindakan, group, tarif) | 302 → index, data benar |
| `kode_tindakan` duplikat | ditolak: *"kode tindakan sudah digunakan."* |
| Kode lowercase `lab-0099` | dinormalisasi jadi `LAB-0099` |
| Group sync pivot | pivot lama soft-delete, insert ulang |
| Hapus tindakan | soft-delete `tindakan` + `tarif` + pivot group |
| Resolusi tarif | `tarif(1, 2)` = 65.000 (kelas 2); `tarif(1, null)` = 50.000 (default) |
| Kelas kosong → fallback default | `tarif(12, 5)` = 45.000 (default), bukan 0 |
| Order Lab: satuan/nilai manual per baris | tersimpan per baris, tidak bergeser walau indeks lompat |
| Order Lab: harga ter-snapshot dari Master Tarif | 75.000 / 50.000 sesuai hak kelas |
| Order Lab: tindakan unit lain | ditolak: *"tidak termasuk dalam Group Tindakan bagian tujuan terpilih."* |

### Gotcha yang ditemukan saat implementasi

1. **`Rule::unique()->ignore($id)` memakai kolom `id` sebagai default.** Tabel `tindakan` &
   `kategori_tindakan` tidak punya kolom `id` (PK custom), jadi harus eksplisit:
   `Rule::unique('tindakan', 'kode_tindakan')->ignore($ignore, 'tindakan_id')`.
   Tanpa itu update melempar `Unknown column 'id' in 'WHERE'` (HTTP 500).
2. **`UserSeeder` memberi admin akses ke semua sub_menu aktif secara dinamis**, jadi 3
   sub_menu baru otomatis bisa diakses user admin tanpa seeding `user_akses` manual.
3. **Akses EMR** — form `laboratorium` (form_id 6) punya `akses_ehr` untuk
   profesi 1/2/13 saja. `superadmin` (pegawai 1) profesinya 6, jadi `/emr/form/laboratorium/...`
   balas 403 untuk admin; pakai user `dokter` (profesi 1) untuk menguji alur order.
4. **`migrate:fresh --seed` menghapus data uji manual** — setelah smoke test, jalankan ulang
   `migrate:fresh --seed` agar DB kembali ke baseline seeder.
