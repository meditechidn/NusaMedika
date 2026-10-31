# Konsep Form EMR: Catatan Medis Lanjutan (Resume Medis, Care Plan, DAR, Visum)

> **Pembaruan:** tabel `bill_temp` dan model `BillTemp` sudah dihapus. Billing disatukan di tabel `billing` (penanda selesai = `billing.status_selesai`). Referensi `bill_temp` di bawah adalah konteks historis.

**Status:** Rancangan / Konsep — belum diimplementasikan
**Tanggal:** 2026-10-10
**Acuan:** [`PANDUAN_IMPLEMENTASI_FORM_EMR.md`](PANDUAN_IMPLEMENTASI_FORM_EMR.md),
[`ANALISIS_KEKURANGAN_FORM.md`](ANALISIS_KEKURANGAN_FORM.md) §3 P1/P2,
[`AGENTS.md`](../AGENTS.md)
**Sumber data:** SIMRS Tenriawaru (legacy PHP/PostgreSQL) di
`/simrs_tenriawaru/FE/lib/modul/`

---

## 1. Ringkasan

Dokumen ini mencakup 4 form yang menutup domain "catatan medis inti" yang masih
kosong di NusaMedika: resume medis rawat inap/IGD, rencana asuhan (care plan),
D-Rekognisi, dan visum et repertum.

| form_id | nama_form | slug | id_dash_menu | ri | rj | igd | mcu | objek baru |
|---|---|---|---|---|---|---|---|---|
| 16 | Resume Medis | `resume_medis` | `1.13` | 1 | 0 | 0 | 0 | 178–188 (11) |
| 20 | Care Plan | `care_plan` | `1.14` | 1 | 1 | 1 | 0 | 189–195 (7) |
| 21 | DAR | `dar` | `1.15.6` | 1 | 0 | 1 | 0 | 196 (1) |
| 57 | Catatan Medis Visum | `catatan_medis_visum` | `1.15` | 0 | 0 | 1 | 0 | 197–199 (3) |

> **Koreksi alokasi menu:** Ketiganya berada di `dashboard_menu_id = 1`
> ("Catatan Medis"). Sub-menu yang dipakai: **13 = Resume Medis**,
> **14 = Care Plan**, **15 = Catatan Medis Visum** (band sub dokumen ini
> **13–32**, `ALOKASI_ID_GLOBAL.md` §3).
> **Tidak ada form dengan slug `dar` pada sub-menu terpisah** — DAR memakai
> sub-menu 15 yang DIBAGI dengan Visum lewat **extra 6**
> (`dashboard_menu_sub_extra_id = 6`, `nama_sub_menu_extra = 'DAR'`),
> sedangkan `id_dash_menu = '1.15'` milik `catatan_medis_visum` (leaf tanpa extra).
> Detail & alasannya di §3.5.
>
> **Objek 186 (`Dirujuk Ke`) DIDEKLARASIKAN di dokumen ini** (form 16 Resume
> Medis, band objek 178–199) dan dipakai ulang oleh **form 18 Pemulangan Pasien**
> di [`KONSEP_DISCHARGE_PLANNING.md`](KONSEP_DISCHARGE_PLANNING.md). Ini **sah**:
> `ALOKASI_ID_GLOBAL.md` §1 menetapkan 186 sebagai satu-satunya objek > 177 yang
> boleh dipakai lintas dokumen. Tidak ada objek lain yang boleh di-*reuse*
> lintas dokumen.
>
> **Catatan `id_dash_menu` vs tabel §6 ledger.** `ALOKASI_ID_GLOBAL.md` §6
> menulis `1.15` untuk form 21 dan `1.16` untuk form 57. Kolom itu **indikatif**
> (lihat catatan di akhir §6 ledger). Yang mengikat adalah baris `$subMenus`/
> `$extras` yang benar-benar ditulis dokumen ini: sub **15** = `Catatan Medis
> Visum` (leaf tanpa extra → `catatan_medis_visum` = `1.15`) dan extra **6**
> di bawahnya = `DAR` (→ `dar` = `1.15.6`). Karena hanya **tiga** sub yang
> dipakai (13, 14, 15), tidak ada slot 16 yang perlu dialokasikan.
>
> **Rentang objek:** dokumen ini memakai `objek_id` **178–199** (22 objek baru).
> Semua vital sign, GCS, EWS, ICD, Farmasi/Tim, dan Keterangan **me-reuse objek
> 1–177** yang sudah ada. Allokasi berikutnya (dokumen discharge planning)
> mulai dari **200**.

### Ringkasan sebaran objek baru

| Rentang | Milik | Jumlah |
|---|---|---|
| 178–188 | Form 16 Resume Medis | 11 |
| 189–195 | Form 20 Care Plan | 7 |
| 196 | Form 21 DAR | 1 |
| 197–199 | Form 57 Catatan Medis Visum | 3 |
| **Total** | | **22** |

---

## 2. Sumber Referensi Legacy

| File legacy | Kontribusi ke desain |
|---|---|
| `FE/lib/modul/resume_medis.php` | Shell form 33 "Resume Medis". Menentukan urutan section (`$dataForm`): KONDISI AWAL → DATA PENUNJANG → DIAGNOSA RESUME → DATA TERAPI OBAT → LAPORAN OPERASI → HISTORY RAWAT INAP → KONTROL POST RAWAT → UPLOAD FILE RESUME → TANDATANGAN. Sumber metadata `tgl_pertemuan` / `jam_pertemuan`. Menentukan mode cetak (`printThis`, Word, `ExportPDF` → `resume_medis_pdf.php`). |
| `FE/lib/modul/resume_.php` | Section **KONDISI AWAL** (form 16). Field: `keluhan`, `indikasi_rawat_inap`, `riwayat_kesehatan_saat_ini`, `riwayat`; blok TTV (`sistolik`, `diastolik`, `nadi`, `pernapasan`, `suhu`, `skala_nyeri`, `ews`); blok GLASGOW COMA SCALE (`gcs_eye` 4→1, `gcs_motorik` 6→1, `gcs_verbal` 5→1, `gcs_score` readonly); tabel **12 organ pemeriksaan fisik** (`$arr_pemeriksaan_fisik`) dengan teks normal default per organ; tabel rekap Implementasi. |
| `FE/lib/modul/diagnosa_resume.php` | Section **DIAGNOSA RESUME** (form 16). Field: `diagnosis` (Diagnosa Primer + `objek_id=47`), `diag_sekunder`, `prosedur_txt`, `ventilator` (radio 1=Tidak/2=Ya), `komplikasi`, `alasan` (`indikatorPulang()`), `recomendation`, `rujukannya` (Puskesmas/Faskes/RS, tampil bila `alasan=2`), `makassar` (Ya/Tidak), `wafat` (DOA / Meninggal Di IGD / <48 Jam / >48 Jam / Lahir Meninggal) + `tanggal_wafat` + `jam_wafat`. |
| `FE/lib/modul/penunjang_resume.php` + `FE/lib/modul/HasilResumeLab.php` | Section **DATA PENUNJANG** — **read-only**, dikumpulkan dari `order_lab`/`lab_hasil`, `hasil_rad`/`hasil_rad_detail`, `bill_temp_detail`, dan form Bundle VAP / Assesment Transfusi. Tidak disimpan di form 16. |
| `FE/lib/modul/terapiObat_resume.php` | Section **DATA TERAPI OBAT** — read-only dari `peresepan_obat` + `obat_pulang.php`. Di NusaMedika menjadi data turunan Order Resep (form 5). Tidak disimpan di form 16. |
| `FE/lib/modul/laporan_operasi_resume.php` | Section **LAPORAN OPERASI** — read-only dari form "Laporan Operasi" (objek legacy 346/347/365). Tidak disimpan di form 16. |
| `FE/lib/modul/history_bed.php` + `history_bed_resume.php` | Section **HISTORY RAWAT INAP** — read-only dari `bed_log` (`status_bed_log` 1..7). Tidak disimpan di form 16. |
| `FE/lib/modul/kontrol_resume.php` + `isiresume.php` | Section **KONTROL POST RAWAT** — read-only dari form Konsultasi dengan `jenis = 'Kontrol Rawat Jalan Post Rawat'`. Tidak disimpan di form 16. |
| `FE/lib/modul/resume_keperawatan.php` | Section resume keperawatan (A. Masalah Perawatan, B. Tindakan Perawat, C. Evaluasi, D. Catatan Pengobatan). **Di luar cakupan dokumen ini** — masuk `KONSEP_ASUHAN_KEPERAWATAN_LANJUTAN.md`. |
| `FE/lib/modul/resume_medis_pdf.php` | Referensi tata letak PDF resume (373 baris): kop surat, identitas pasien, lalu seluruh section di atas, `pagebreak` antar section. |
| `FE/lib/modul/care_plan.php` | Form 2 "Care Plan". Field: `tgl_pertemuan`, `jam_pertemuan`, `tempat_pertemuan`, `diagnosis` (ICD, prefill dari diagnosa primer dokter terakhir), `care_plan`, `non_farmakologis`, `farmakologis`, `lama_rawat` (select 0–100 hari), `target_perawatan`, `kriteria_pemulangan`, `tgl_evaluasi`, `dpjp_utama`, `dpjp_konsultan` (multi), `doker_ruangan` (multi), `perawat_ruangan`, `ahli_gizi`, `farmasi`. |
| `FE/lib/modul/care_plan_hd.php` | Varian Care Plan untuk pasien hemodialisa: `riwayat_penyakit_sekarang` (DM/Hipertensi/Batu Ginjal/Glomerulo Nefritis/Pyelo Nefritis), `hasil_echo`, `hasil_thorax`, `akses_hd` (CDL Long Term / CDL Short Term / AV Shunt / FEMORAL), `bbk`, `jadwal_frekuensi_hd` (2x Seminggu / 3x Seminggu / HD Temporary), `type_hd` (Hemodialysis / SLED / Squential Ultrafiltration / Hemodiafiltrasi), `kesadaran` (Pre/Post Dilution), `target_ufg`, `target_durasi`, `freeheparine_dosis`, `regheparine_dosis`, `100heparine_dosis`, `perencanaan_hd`. **Di luar cakupan** — masuk `KONSEP_ICU_HEMODIALISA.md`. |
| `FE/lib/modul/dar_entry.php` | Form 5 "Entry Catatan Medis DAR". Field eksak: `data` (textarea, objek 35), `aksi` (textarea, objek 36), `respon` (textarea, objek 3), tombol RESET/CANCEL/SIMPAN. |
| `FE/lib/modul/dar_act.php` | Menyimpan `emr` + `emr_detail` per variabel; soft-delete (`status_batal`) saat `aksi=delete`. Konfirmasi pola `EmrHelper::insert/update/delete`. |
| `FE/lib/func/keperawatan_template.php` (`assesment_perawat_dalam_form`) | Header read-only yang ditampilkan di atas form DAR: ringkasan Pengkajian Awal Keperawatan (Keadaan Umum, Kesadaran, TD, BB, TB, Suhu, Nadi, Keluhan Utama, Risiko Jatuh). Di NusaMedika menjadi panel **read-only** dari `EmrHelper::latestValuesByVariabel()`. |
| `FE/lib/modul/catatan_medis_visum.php` | Form 44 "Catatan Medis Visum". Field: `jenis_visum` (LAKA / KLL, KDRT, LAIN-LAIN), `kode_visum` (dari tabel `urut_visum`), `dokter_visum` (dokter spesialisasi IGD), `bagian_id` (hard-coded IGD), `permintaan_visum`, `benda_bukti_identifisir`, `hasil_visum`, `kesimpulan_visum`, `kelainan_sebab`, `kelainan_akibat`. |
| `FE/lib/cetak/cetak_visum.php` | Referensi cetak Visum: judul **PRO JUSTITIA**, kop (`kop_vector.php`), redaksi "Saya yang bertanda tangan dibawah ini…", blok identitas benda bukti (No MR, Nama, Bangsa, Jenis Kelamin, Umur, Tempat Tinggal), heading HASIL PEMERIKSAAN & KESIMPULAN, blok tanda tangan dokter. |
| `FE/lib/func/_func.php` (`indikatorPulang`) | Daftar nilai resmi kondisi pulang yang dipakai di `diagnosa_resume.php`: `1` Atas Persetujuan Dokter, `2` Dirujuk, `3` Atas Permintaan Sendiri, `4` Meninggal, `5` Lainnya. |

---

## 3. Form 16 — Resume Medis

### 3.1 Rasional & tujuan klinis

Resume medis adalah **dokumen ringkasan episode perawatan**. Ia lahir saat pasien
dipulangkan (atau kasus ditutup di IGD), dibaca oleh dokter pemeriksa berikutnya,
penjamin, dan auditor klinis. NusaMedika saat ini hanya punya SOAP per episode dan
tanda vital harian — tidak ada dokumen yang mengompilasi seluruh episode.

Tujuan klinis form ini:

1. Membekukan **ringkasan naratif** episode: keluhan utama, indikasi rawat inap,
   riwayat penyakit sekarang & sebelumnya, hasil pemeriksaan fisik awal.
2. Membekukan **ringkasan objektif**: tanda vital saat admisi, skor nyeri, EWS, GCS.
3. Membekukan **ringkasan diagnostik & terapeutik**: diagnosa primer, sekunder,
   prosedur, komplikasi.
4. Membekukan **ringkasan akhir**: kondisi saat pulang, tujuan rujukan, atau
   keterangan kematian.
5. Menyediakan **data turunan read-only** untuk section yang di legacy diambil
   dari form lain (penunjang, terapi obat, laporan operasi, history bed,
   kontrol post rawat) — section ini **tidak disimpan** di `emr_detail`.

### 3.2 Struktur Field

Bagian A — Kondisi Awal (diisi manual, wajib)

| variabel | objek_id | nama_objek | tipe kontrol | wajib | keterangan |
|---|---|---|---|---|---|
| `tanggal_resume` | 178 | Tanggal Resume Medis | date | ya | Tanggal penyusunan resume. Default `today()`, boleh diubah. |
| `waktu_resume` | 179 | Waktu Resume Medis | time (H:i) | ya | Jam penyusunan resume. |
| `keluhan_utama` | 13 | Keluhan Utama | textarea | ya | **Reuse objek 13.** Chief complaint episode. Di-prefill dari EMR terakhir bila ada. |
| `indikasi_rawat_inap` | 180 | Indikasi Rawat Inap | textarea | ya | Legacy `indikasi_rawat_inap` (objek 68). Wajib hanya bila episode RI; opsional IGD/RJ. |
| `riwayat_kesehatan_saat_ini` | 42 | Riwayat Penyakit Sekarang | textarea | ya | **Reuse objek 42.** Legacy `riwayat_kesehatan_saat_ini` (objek 61) = Present Disease History. |
| `riwayat_penyakit_dahulu` | 41 | Riwayat Penyakit Sebelumnya | textarea | ya | **Reuse objek 41.** Legacy `riwayat` (objek 21) = Past Disease History. |

Bagian B — Tanda Vital & Skor (diisi manual; turunan dihitung ulang di server)

| variabel | objek_id | nama_objek | tipe kontrol | wajib | keterangan |
|---|---|---|---|---|---|
| `td_sistolik` | 6 | Tekanan Darah Sistolik | number | ya | Reuse. Parameter EWS. |
| `td_diastolik` | 7 | Tekanan Darah Diastolik | number | ya | Reuse. |
| `nadi` | 10 | Nadi | number | ya | Reuse. Parameter EWS. |
| `pernapasan` | 12 | Pernapasan | number | ya | Reuse. Parameter EWS. |
| `suhu` | 11 | Suhu | decimal | ya | Reuse. Parameter EWS. |
| `skor_nyeri` | 140 | Skor Nyeri | select 0–10 | ya | Reuse objek form 11/13. |
| `total_ews` | 142 | Total Skor EWS | readonly | — | **Turunan** `App\Helpers\EwsHelper::hitung()`. Nilai dari browser dibuang. |
| `kategori_ews` | 143 | Kategori Risiko EWS | badge readonly | — | **Turunan** `EwsHelper::KATEGORI_WARNA`. |
| `gcs_e` | 54 | GCS Eye | select 1–4 | ya | Reuse. |
| `gcs_m` | 55 | GCS Motorik | select 1–6 | ya | Reuse. |
| `gcs_v` | 56 | GCS Verbal | select 1–5 | ya | Reuse. |
| `gcs_jumlah` | 57 | GCS Score | readonly | — | **Turunan** = `gcs_e + gcs_m + gcs_v`, dihitung ulang di `filteredData()`. |

Bagian C — Pemeriksaan Fisik (12 organ, **satu objek**)

Legacy menyimpan tiap organ pada objek terpisah (115–126 untuk flag, 127–138
untuk teks). Untuk menghemat objek dan menjaga laporan antar form, kedua belas
organ dipetakan ke **satu objek baru 188** dengan **12 variabel bersuffix** —
pola yang sama dengan `tanggal_covid_1`/`tanggal_covid_2` → objek 62 di form 3
dan `vap_1..vap_10` → objek 158–167 di form 14.

| variabel | objek_id | nama_objek | tipe kontrol | wajib | keterangan |
|---|---|---|---|---|---|
| `pf_kepala` | 188 | Pemeriksaan Fisik | textarea | ya | Legacy `kepala_txt` — default "Tidak ditemukan atropi m. temporalis, parese n VII tidak ditemukan". |
| `pf_mata` | 188 | Pemeriksaan Fisik | textarea | ya | Default "Konjungtiva tidak pucat, sclera tidak ikterik, …". |
| `pf_tht` | 188 | Pemeriksaan Fisik | textarea | ya | Telinga, Hidung dan Tenggorokan. |
| `pf_gigidanmulut` | 188 | Pemeriksaan Fisik | textarea | ya | |
| `pf_leher` | 188 | Pemeriksaan Fisik | textarea | ya | |
| `pf_toraks` | 188 | Pemeriksaan Fisik | textarea | ya | |
| `pf_jantung` | 188 | Pemeriksaan Fisik | textarea | ya | |
| `pf_paru` | 188 | Pemeriksaan Fisik | textarea | ya | |
| `pf_abdomen` | 188 | Pemeriksaan Fisik | textarea | ya | |
| `pf_kelenjar` | 188 | Pemeriksaan Fisik | textarea | ya | Kelenjar Getah Bening. |
| `pf_genitalia` | 188 | Pemeriksaan Fisik | textarea | ya | Ditambah `jenis_kelamin` sebagai konteks tampilan (diturunkan dari `pasien`, **tidak disimpan**). |
| `pf_ekstremitas` | 188 | Pemeriksaan Fisik | textarea | ya | |

> Default teks per organ diambil dari array literal `datas` di
> `resume_medis.php:783-794`. Teks default **hanya prefill**, tidak disimpan
> terpisah — petugas bebas mengubahnya.

Bagian D — Diagnosa Resume

| variabel | objek_id | nama_objek | tipe kontrol | wajib | keterangan |
|---|---|---|---|---|---|
| `diagnosa_primer` | 181 | Diagnosa Primer Resume | textarea | ya | Legacy `diagnosis` (objek 47). |
| `diagnosa_sekunder` | 182 | Diagnosa Sekunder Resume | textarea | tidak | Legacy `diag_sekunder`. |
| `prosedur` | 183 | Prosedur yang Dilakukan | textarea | tidak | Legacy `prosedur_txt` (objek 1434), kode ICD-9. |
| `komplikasi` | 184 | Komplikasi Penyakit | textarea | tidak | Legacy `komplikasi` (objek 451). Popover "Dilarang Copy Paste". |

Bagian E — Kondisi Saat Pulang / Rujukan / Meninggal

| variabel | objek_id | nama_objek | tipe kontrol | wajib | keterangan |
|---|---|---|---|---|---|
| `kondisi_saat_pulang` | 185 | Kondisi Saat Pulang | select | ya | Legacy `alasan` (objek 424) memakai `indikatorPulang()`. Opsi memakai key SelectOption **`alasan_pulang`**. |
| `catatan_alasan_pulang` | 77 | Keterangan | textarea | ya | **Reuse objek 77.** Legacy `recomendation`. |
| `dirujuk_ke` | 186 | Dirujuk Ke | radio | kondisional | Legacy `rujukannya` (objek 466): `Puskesmas` / `Faskes` / `Rumah Sakit`. **Wajib bila `kondisi_saat_pulang = Dirujuk`**, disembunyikan selain itu. |
| `meninggal` | 187 | Meninggal | radio | kondisional | Legacy `wafat` (objek 468): `DOA` / `Meninggal Di IGD` / `< 48 Jam` / `> 48 Jam` / `Lahir Meninggal`. **Wajib bila `kondisi_saat_pulang = Meninggal`.** |
| `catatan_kematian` | 77 | Keterangan | datetime | kondisional | **Reuse objek 77.** Legacy `tanggal_wafat` + `jam_wafat` (objek 1211/1212) digabung satu field. **Wajib bila `kondisi_saat_pulang = Meninggal`.** |

Bagian F — Section **read-only** (tidak ada variabel, tidak ada mapping)

| Section legacy | Sumber data di NusaMedika |
|---|---|
| Data Penunjang | `order_laboratorium_detail` + `order_radiologi_detail` pada `registrasi_detail` episode (`EmrHelper::latestValuesByVariabel` tidak cukup — butuh query agregat baru di controller). |
| Data Terapi Obat | `order_resep` (form 5) episode. |
| Laporan Operasi | Disediakan form 92 `laporan_operasi` (dokumen `KONSEP_BEDAH_ANESTESI.md`) — sampai form itu ada, section menampilkan "Belum ada data". |
| History Rawat Inap | Tabel `bed_log` + `BedLog` (fitur Bed Management sudah ada). |
| Kontrol Post Rawat | Form 8 `konsultasi` dengan `jenis_konsultasi = RENCANA_KONTROL`. |
| Tanda Tangan | `pegawai` + `users`; digital sign menyusul. |

### 3.3 Dashboard & Form Master

```php
// ======== $objeks ========
// Sisanya (seluruh tanda vital, GCS, EWS, skor nyeri, keluhan, riwayat,
// tanggal/waktu) REUSE objek yang sudah ada.
178 => 'Tanggal Resume Medis',
179 => 'Waktu Resume Medis',
180 => 'Indikasi Rawat Inap',
181 => 'Diagnosa Primer Resume',
182 => 'Diagnosa Sekunder Resume',
183 => 'Prosedur yang Dilakukan',
184 => 'Komplikasi Penyakit',
185 => 'Kondisi Saat Pulang',
186 => 'Dirujuk Ke',
187 => 'Meninggal',
188 => 'Pemeriksaan Fisik',          // 12 organ -> 12 variabel bersuffix

189 => 'Tempat Pertemuan',           // Care Plan (form 20)
190 => 'Intervensi Non Farmakologis',
191 => 'Intervensi Farmakologis',
192 => 'Perkiraan Lama Rawat (Hari)',
193 => 'Target Perawatan',
194 => 'Kriteria Pemulangan Pasien',
195 => 'Tanggal Evaluasi Care Plan',

196 => 'Tanggal DAR',                // DAR (form 21)

197 => 'Jenis Visum',                 // Catatan Medis Visum (form 57)
198 => 'Nomor Visum Et Repertum',
199 => 'Permintaan Visum',
```

```php
// ======== $menus ========
// Menu 1 "Catatan Medis" sudah ada; tidak ditambah.

$subMenus = [
    // ... baris 1–12 yang sudah ada ...
    // Menu 1 "Catatan Medis" — Resume Medis (tanpa extra, link langsung).
    // Str::slug('Resume Medis','_') = "resume_medis" WAJIB sama dengan form.slug.
    ['dashboard_menu_sub_id' => 13, 'dashboard_menu_id' => 1, 'nama_sub_menu' => 'Resume Medis'],
    // Menu 1 "Catatan Medis" — Care Plan (tanpa extra, link langsung).
    // Str::slug('Care Plan','_') = "care_plan" WAJIB sama dengan form.slug.
    ['dashboard_menu_sub_id' => 14, 'dashboard_menu_id' => 1, 'nama_sub_menu' => 'Care Plan'],
    // Menu 1 "Catatan Medis" — Catatan Medis Visum (tanpa extra, link langsung).
    // Str::slug('Catatan Medis Visum','_') = "catatan_medis_visum" WAJIB sama dengan form.slug.
    ['dashboard_menu_sub_id' => 15, 'dashboard_menu_id' => 1, 'nama_sub_menu' => 'Catatan Medis Visum'],
];

$forms = [
    // Resume Medis: ringkasan episode rawat inap / IGD. Tersedia di RI, RJ, IGD;
    // tidak di MCU. id_dash_menu "1.13" (menu 1 "Catatan Medis", sub 13 tanpa extra).
    ['form_id' => 16, 'nama_form' => 'Resume Medis', 'slug' => 'resume_medis', 'id_dash_menu' => '1.13', 'ri' => 1, 'rj' => 0, 'igd' => 0, 'mcu' => 0],
    // Care Plan: rencana asuhan medis. Tersedia di RI, RJ, IGD.
    // id_dash_menu "1.14".
    ['form_id' => 20, 'nama_form' => 'Care Plan', 'slug' => 'care_plan', 'id_dash_menu' => '1.14', 'ri' => 1, 'rj' => 1, 'igd' => 1, 'mcu' => 0],
    // DAR: catatan pengakuan/kejadian (D-Rekognisi). Tersedia di RI, RJ, IGD.
    // id_dash_menu "1.15.6" — sub 15 DENGAN extra 6 "DAR".
    ['form_id' => 21, 'nama_form' => 'DAR', 'slug' => 'dar', 'id_dash_menu' => '1.15.6', 'ri' => 1, 'rj' => 0, 'igd' => 1, 'mcu' => 0],
    // Catatan Medis Visum: visum et repertum. KHUSUS IGD (dokter IGD yang membuat).
    // id_dash_menu "1.15" — sub 15 tanpa extra, harus mendahului extra 6 dalam urutan.
    ['form_id' => 57, 'nama_form' => 'Catatan Medis Visum', 'slug' => 'catatan_medis_visum', 'id_dash_menu' => '1.15', 'ri' => 0, 'rj' => 0, 'igd' => 1, 'mcu' => 0],
];
```

Karena form 21 memakai **extra**, baris extra pun wajib di-seed:

```php
$extras = [
    // ... baris 1–5 yang sudah ada ...
    // Sub Menu 15 "Catatan Medis Visum"
    ['dashboard_menu_sub_extra_id' => 6, 'dashboard_menu_sub_id' => 15, 'nama_sub_menu_extra' => 'DAR'],
];
```

> `nama_sub_menu_extra` **wajib persis `DAR`** (tanpa keterangan dalam kurung) —
> `Str::slug('DAR','_')` = `dar`, sama dengan `form.slug` form 21 (PANDUAN §2.1).

> **Gotcha `id_dash_menu` 3 tingkat.** `header_ehr` memakai
> `CONCAT_WS('.', menu_id, sub_id, extra_id)` dan `CONCAT_WS` **melewati NULL**.
> Sub 15 punya dua leaf: `catatan_medis_visum` (tanpa extra → `"1.15"`) dan
> `dar` (lewat extra 6 → `"1.15.6"`). Perhatikan jebakan `==`:
> `"1.15" == "1.15.6"` bernilai **false**, jadi tidak bentrok — tapi
> `"1.1" == "1.10"` bernilai **true** dan sudah pernah menjadi bug
> (`AksesEhrController`). Semua perbandingan `id_dash_menu` WAJIB `===`.
>
> **Nama extra `DAR`, bukan `DAR (D-Rekognisi)`.** Aturan
> `form.slug == Str::slug(nama_sub_menu_extra,'_')` (PANDUAN §2.1) mengharuskan
> nama extra persis `DAR` agar slug-nya `dar`. Nama `DAR (D-Rekognisi)` akan
> menghasilkan slug `dar_d_rekognisi` sehingga form 21 jadi yatim. Keterangan
> "D-Rekognisi" tetap dipakai pada `nama_form` dan pada judul dokumen, bukan
> pada nama sub-extra yang menjadi sumber slug.

### 3.4 Mapping

```php
$mapping[16] = [
    // --- Kondisi awal ---
    'tanggal_resume'            => 178,
    'waktu_resume'              => 179,
    'keluhan_utama'             => 13,   // reuse "Keluhan Utama"
    'indikasi_rawat_inap'       => 180,
    'riwayat_kesehatan_saat_ini'=> 42,   // reuse "Riwayat Penyakit Sekarang"
    'riwayat_penyakit_dahulu'   => 41,   // reuse "Riwayat Penyakit Sebelumnya"

    // --- Tanda vital & skor ---
    'td_sistolik'               => 6,
    'td_diastolik'              => 7,
    'nadi'                      => 10,
    'pernapasan'                => 12,
    'suhu'                      => 11,
    'skor_nyeri'                => 140,  // reuse
    'total_ews'                 => 142,  // TURUNAN (EwsHelper)
    'kategori_ews'              => 143,  // TURUNAN (EwsHelper)
    'gcs_e'                     => 54,
    'gcs_m'                     => 55,
    'gcs_v'                     => 56,
    'gcs_jumlah'                => 57,   // TURUNAN

    // --- Pemeriksaan fisik: 12 organ -> SATU objek (188) ---
    'pf_kepala'                 => 188,
    'pf_mata'                   => 188,
    'pf_tht'                    => 188,
    'pf_gigidanmulut'           => 188,
    'pf_leher'                  => 188,
    'pf_toraks'                 => 188,
    'pf_jantung'                => 188,
    'pf_paru'                   => 188,
    'pf_abdomen'                => 188,
    'pf_kelenjar'               => 188,
    'pf_genitalia'              => 188,
    'pf_ekstremitas'            => 188,

    // --- Diagnosa resume ---
    'diagnosa_primer'           => 181,
    'diagnosa_sekunder'         => 182,
    'prosedur'                  => 183,
    'komplikasi'                => 184,

    // --- Kondisi pulang / rujukan / kematian ---
    'kondisi_saat_pulang'        => 185,
    'catatan_alasan_pulang'     => 77,   // reuse "Keterangan"
    'dirujuk_ke'                => 186,
    'meninggal'                 => 187,
    'catatan_kematian'          => 77,   // reuse "Keterangan"
];
```

> `catatan_alasan_pulang` dan `catatan_kematian` **sengaja sama-sama** objek 77
> (dua variabel berbeda → dua baris `emr_detail`). Keduanya belum pernah
> terisi bersamaan (satu untuk dirujuk, satu untuk meninggal), jadi tidak ada
> risiko tumpang tindih saat baca `pluck('value','variabel')`.
>
> `pf_genitalia` **tidak** memakai objek 153 `jenis_kelamin` seperti di legacy —
> jenis kelamin diambil dari tabel `pasien`, bukan disimpan per episode.

### 3.5 Akses EHR

```php
// Resume Medis (form 16): Dokter & Perawat create/read/update/delete.
// WAJIB di-seed: EmrDashboardController INNER JOIN akses_ehr.
['profesi_id' => 1, 'form_id' => 16, 'level_id' => 1, 'bagian_id' => null, 'akses_create' => 1, 'akses_read' => 1, 'akses_update' => 1, 'akses_delete' => 1],
['profesi_id' => 2, 'form_id' => 16, 'level_id' => 1, 'bagian_id' => null, 'akses_create' => 1, 'akses_read' => 1, 'akses_update' => 1, 'akses_delete' => 1],

// Care Plan (form 20): Dokter & Perawat create/read/update/delete.
['profesi_id' => 1, 'form_id' => 20, 'level_id' => 1, 'bagian_id' => null, 'akses_create' => 1, 'akses_read' => 1, 'akses_update' => 1, 'akses_delete' => 1],
['profesi_id' => 2, 'form_id' => 20, 'level_id' => 1, 'bagian_id' => null, 'akses_create' => 1, 'akses_read' => 1, 'akses_update' => 1, 'akses_delete' => 1],

// DAR (form 21): Dokter create/read/update/delete; Perawat read saja
// (visum forensik hanya boleh dibuat dokter).
['profesi_id' => 1, 'form_id' => 21, 'level_id' => 1, 'bagian_id' => null, 'akses_create' => 1, 'akses_read' => 1, 'akses_update' => 1, 'akses_delete' => 1],
['profesi_id' => 2, 'form_id' => 21, 'level_id' => 1, 'bagian_id' => null, 'akses_create' => 0, 'akses_read' => 1, 'akses_update' => 0, 'akses_delete' => 0],

// Catatan Medis Visum (form 57): HANYA Dokter create/read/update/delete.
// Perawat TIDAK diberi akses sama sekali (bukan read) karena dokumen visum
// bertanda tangan dan merupakan dokumen bermeterai. Perawat tidak diberi baris akses. dan merupakan dokumen legal. Perawat tidak diberi baris akses,
// sehingga form tidak muncul di dashboard perawat dan URL langsung 403.
['profesi_id' => 1, 'form_id' => 57, 'level_id' => 1, 'bagian_id' => null, 'akses_create' => 1, 'akses_read' => 1, 'akses_update' => 1, 'akses_delete' => 1],
```

Tambahkan `EmrHelper::backfillObjekId(16);` / `(20)` / `(21)` / `(57)` di
`EmrMasterSeeder` setelah loop mapping.

### 3.6 Validasi

```php
private function validated(Request $request): array
{
    return array_merge([
        'indikasi_rawat_inap'     => null,
        'diagnosa_sekunder'       => null,
        'prosedur'                => null,
        'komplikasi'              => null,
        'dirujuk_ke'              => null,
        'meninggal'               => null,
        'catatan_kematian'        => null,
        'pf_kepala'               => null,  // … s/d pf_ekstremitas
    ], $request->validate([
        'tanggal_resume'             => 'required|date',
        'waktu_resume'               => 'required|date_format:H:i',
        'keluhan_utama'              => 'required|string|max:1000',
        'riwayat_kesehatan_saat_ini' => 'required|string|max:2000',
        'riwayat_penyakit_dahulu'    => 'required|string|max:2000',
        'indikasi_rawat_inap'        => 'nullable|string|max:1000',

        'td_sistolik'                => 'required|integer|min:40|max:300',
        'td_diastolik'               => 'required|integer|min:20|max:200',
        'nadi'                       => 'required|integer|min:20|max:250',
        'pernapasan'                 => 'required|integer|min:5|max:60',
        'suhu'                       => 'required|numeric|min:30|max:45',
        'skor_nyeri'                 => 'required|integer|min:0|max:10',
        'gcs_e'                      => 'required|integer|min:1|max:4',
        'gcs_m'                      => 'required|integer|min:1|max:6',
        'gcs_v'                      => 'required|integer|min:1|max:5',

        'diagnosa_primer'            => 'required|string|max:2000',
        'diagnosa_sekunder'          => 'nullable|string|max:2000',
        'prosedur'                   => 'nullable|string|max:2000',
        'komplikasi'                 => 'nullable|string|max:2000',

        'kondisi_saat_pulang'         => ['required', Rule::in(array_column(SelectOption::get('alasan_pulang'), 'value'))],
        'catatan_alasan_pulang'      => 'required|string|max:1000',

        // Aturan kondisional — dicek di filteredData()/store(), bukan di validator
        // karena bergantung pada nilai field lain.
    ]));
}
```

Aturan kondisional (di `store()` / `update()` **sebelum** `EmrHelper::insert()`):

| Kondisi | Aturan |
|---|---|
| `kondisi_saat_pulang = 'Dirujuk'` | `dirujuk_ke` wajib salah satu dari `Puskesmas` / `Faskes` / `Rumah Sakit`. |
| `kondisi_saat_pulang = 'Meninggal'` | `meninggal` wajib salah satu dari `DOA` / `Meninggal Di IGD` / `< 48 Jam` / `> 48 Jam` / `Lahir Meninggal`, dan `catatan_kematian` (tanggal+jam) wajib. |
| Selain dua di atas | `dirujuk_ke`, `meninggal`, `catatan_kematian` **dibuang dari payload** di `filteredData()` supaya tidak ada baris yatim di `emr_detail`. |
| `jenis_rawat = RJ` | `indikasi_rawat_inap` opsional; form tetap memvalidasi kolom lain sebagai wajib. |
| Field computed | `total_ews`, `kategori_ews`, `gcs_jumlah` **tidak divalidasi**; nilai dari browser dibuang dan dihitung ulang `EwsHelper::hitung()` + penjumlahan GCS di `filteredData()`. |

### 3.7 Cetak

**Wajib.** Mengikuti `resume_medis_pdf.php` dan mode `printThis` di
`resume_medis.php`.

- Route: `GET /emr/resume_medis/print/{emr_id}` → `ResumeMedisController@print`
  (mengikuti pola `Soap` & `Konsultasi`, PANDUAN §Step 10).
- View: `resources/views/moduls/EMR/ResumeMedis/print.blade.php`
- Layout `@extends('layouts.print')` (baru) atau inline CSS `A4` portrait.
- Isi dokumen:
  1. Kop surat rumah sakit + judul **RESUME MEDIS RAWAT INAP / IGD / RAWAT JALAN**.
  2. Identitas pasien (No MR, nama, JK, umur, alamat, nasabah, DPJP, ruang).
  3. **Kondisi Awal** — keluhan, indikasi rawat inap, riwayat, TTV, EWS, skor nyeri, GCS, 12 organ pemeriksaan fisik.
  4. **Data Penunjang** — read-only.
  5. **Diagnosa Resume** — primer, sekunder, prosedur, komplikasi, kondisi pulang, rujukan/kematian.
  6. **Data Terapi Obat** — read-only.
  7. **Laporan Operasi** — read-only.
  8. **History Rawat Inap** — read-only.
  9. **Kontrol Post Rawat** — read-only.
  10. Blok tanda tangan dokter.
- `pagebreak` antar section (legacy memakai `<pagebreak>`).

---

## 4. Form 20 — Care Plan

### 4.1 Rasional & tujuan klinis

Care plan adalah **rencana asuhan tertulis** yang mengikat diagnosa → intervensi →
target terukur → kriteria pemulangan → jadwal evaluasi. Legacy menyimpannya
sebagai satu formulir panjang dengan blok "Target Terukur" (perkiraan lama
rawat, target perawatan, kriteria pemulangan) dan blok "Nama dan Tanda Tangan
Tim yang Hadir" (DPJP utama, DPJP konsultan, dokter ruang, perawat ruang, ahli
gizi, farmasi).

Tujuan klinis di NusaMedika:

1. Menetapkan **target terukur** yang bisa dievaluasi ulang — pembeda utama dari SOAP.
2. Menetapkan **kriteria pemulangan** sebagai kontrak dengan Discharge Planning (form 17).
3. Merekam **tim yang hadir** sehingga pada setiap episode ada akuntabilitas.

### 4.2 Struktur Field

| variabel | objek_id | nama_objek | tipe kontrol | wajib | keterangan |
|---|---|---|---|---|---|
| `tanggal_care_plan` | 155 | Tanggal Observasi | date | ya | **Reuse objek 155.** Preseden: form 14 Bundle VAP memetakan `tanggal_bundle` → 155. |
| `waktu_care_plan` | 156 | Waktu Observasi | time (H:i) | ya | **Reuse objek 156.** |
| `tempat_pertemuan` | 189 | Tempat Pertemuan | text | ya | Legacy `tempat_pertemuan` (objek 46). Default: nama ruang perawatan pasien. |
| `diagnosis_icd` | 40 | Diagnosa Medis | select2 ICD | ya | **Reuse objek 40.** Legacy `diagnosis` (objek 47). Prefill dari diagnosa primer dokter terakhir pada `registrasi_detail` yang sama. |
| `care_plan` | 4 | Planning (P) | textarea | ya | **Reuse objek 4.** Legacy `care_plan` (objek 48). |
| `non_farmakologis` | 190 | Intervensi Non Farmakologis | textarea | ya | Legacy `non_farmakologis` (objek 49). |
| `farmakologis` | 191 | Intervensi Farmakologis | textarea | ya | Legacy `farmakologis` (objek 50). |
| `lama_rawat` | 192 | Perkiraan Lama Rawat (Hari) | select 0–100 | ya | Legacy `lama_rawat` (objek 51). Integer hari. |
| `target_perawatan` | 193 | Target Perawatan | text | ya | Legacy `target_perawatan` (objek 52). |
| `kriteria_pemulangan` | 194 | Kriteria Pemulangan Pasien | text | ya | Legacy `kriteria_pemulangan` (objek 53). Dibaca form 17 saat perencanaan pulang. |
| `tanggal_evaluasi` | 195 | Tanggal Evaluasi Care Plan | date | tidak | Legacy `tgl_evaluasi` (objek 54). Default `tanggal_care_plan + lama_rawat`. |
| `dpjp_utama` | 139 | Dokter Pemeriksa | select (readonly) | ya | **Reuse objek 139.** Legacy `dpjp_utama` (objek 55). Diisi dari `penanggung_rawat.rawat_user_id` → `pegawai`, **bukan** dari input browser. |
| `dpjp_konsultan` | 85 | Dokter Tujuan Konsultasi | select2 multi | tidak | **Reuse objek 85.** Legacy `dpjp_konsultan` (objek 56). Sumber: form 8 Konsultasi pada episode yang sama. |
| `tim_perawatan` | 82 | Petugas Pelaksana | select2 multi | ya | **Reuse objek 82.** Legacy menggabungkan `doker_ruangan` (objek 57), `perawat_ruangan` (58), `ahli_gizi` (59), `farmasi` (60) menjadi satu multi-select "Tim Perawatan". Nilai disimpan **comma-separated** `pegawai_id`. |
| `catatan` | 77 | Keterangan | textarea | tidak | **Reuse objek 77.** |

> **Penyederhanaan yang disengaja.** Legacy punya 6 field anggota tim terpisah
> (`doker_ruangan`, `perawat_ruangan`, `ahli_gizi`, `farmasi` + `dpjp_konsultan`).
> Di NusaMedika digabung menjadi `dpjp_konsultan` (konsultan, reuse objek 85)
> dan `tim_perawatan` (semua anggota di luar DPJP utama, reuse objek 82).
> Alasannya: `pegawai` sudah menyimpan `jabatan_id`/`profesi_id` sehingga peran
> tiap anggota dapat direkonstruksi saat baca — tidak perlu 4 objek terpisah.
> Trade-off dibahas di §8.
>
> **Varian HD di luar cakupan.** `care_plan_hd.php` (hemodialisa) memakai
> variabel yang sama persis until `dpjp_utama`, lalu menambah blok HD. Dokumen
> `KONSEP_ICU_HEMODIALISA.md` yang akan menambah blok tersebut sebagai form
> terpisah (form 102–105); Care Plan tetap menjadi form induknya.

### 4.3 Dashboard & Form Master

Sudah ditulis di §3.3 (sub-menu 14 + `form_id = 20`). Ringkas:

```php
['dashboard_menu_sub_id' => 14, 'dashboard_menu_id' => 1, 'nama_sub_menu' => 'Care Plan'],

['form_id' => 20, 'nama_form' => 'Care Plan', 'slug' => 'care_plan', 'id_dash_menu' => '1.14', 'ri' => 1, 'rj' => 1, 'igd' => 1, 'mcu' => 0],
```

### 4.4 Mapping

```php
$mapping[20] = [
    'tanggal_care_plan'   => 155,  // reuse "Tanggal Observasi"
    'waktu_care_plan'     => 156,  // reuse "Waktu Observasi"
    'tempat_pertemuan'    => 189,
    'diagnosis_icd'       => 40,   // reuse "Diagnosa Medis"
    'care_plan'           => 4,    // reuse "Planning (P)"
    'non_farmakologis'    => 190,
    'farmakologis'        => 191,
    'lama_rawat'          => 192,
    'target_perawatan'    => 193,
    'kriteria_pemulangan' => 194,
    'tanggal_evaluasi'    => 195,
    'dpjp_utama'          => 139,  // reuse "Dokter Pemeriksa"
    'dpjp_konsultan'      => 85,   // reuse "Dokter Tujuan Konsultasi"
    'tim_perawatan'       => 82,   // reuse "Petugas Pelaksana"
    'catatan'             => 77,   // reuse "Keterangan"
];
```

### 4.5 Akses EHR

```php
['profesi_id' => 1, 'form_id' => 20, 'level_id' => 1, 'bagian_id' => null, 'akses_create' => 1, 'akses_read' => 1, 'akses_update' => 1, 'akses_delete' => 1],
['profesi_id' => 2, 'form_id' => 20, 'level_id' => 1, 'bagian_id' => null, 'akses_create' => 1, 'akses_read' => 1, 'akses_update' => 1, 'akses_delete' => 1],
```

### 4.6 Validasi

```php
private function validated(Request $request): array
{
    return array_merge([
        'diagnosa_sekunder'   => null,   // tidak dipakai di form ini
        'tanggal_evaluasi'    => null,
        'dpjp_konsultan'      => null,
        'catatan'             => null,
    ], $request->validate([
        'tanggal_care_plan'   => 'required|date',
        'waktu_care_plan'     => 'required|date_format:H:i',
        'tempat_pertemuan'    => 'required|string|max:255',
        'diagnosis_icd'       => ['required', 'integer', 'exists:icd,icd_id'],
        'care_plan'           => 'required|string|max:3000',
        'non_farmakologis'    => 'required|string|max:3000',
        'farmakologis'        => 'required|string|max:3000',
        'lama_rawat'          => 'required|integer|min:0|max:100',
        'target_perawatan'    => 'required|string|max:500',
        'kriteria_pemulangan' => 'required|string|max:500',
        'tanggal_evaluasi'    => 'nullable|date|after_or_equal:tanggal_care_plan',
        'dpjp_konsultan'      => 'nullable|array',
        'dpjp_konsultan.*'    => 'integer|exists:pegawai,pegawai_id',
        'tim_perawatan'       => 'required|array|min:1',
        'tim_perawatan.*'     => 'integer|exists:pegawai,pegawai_id',
    ]));
}
```

Tambahan di `filteredData()`:

- `dpjp_utama` **tidak** diambil dari request; di-set dari
  `PenanggungRawat::where('registrasi_id', …)->rawat_user_id` → `pegawai`.
  `penanggung_rawat.rawat_user_id` sudah diisi user dokter di
  `PasienDummySeeder` dan form registrasi.
- `diagnosis_icd` disimpan **ID saja**; nama/kode ICD di-resolve saat tampilan.
- `tim_perawatan` disimpan sebagai `implode(',', …)` agar muat di kolom `value`.

### 4.7 Cetak

**Wajib** — care plan adalah dokumen yang ditandatangani dan ditempel di chart
pasien.

- Route `GET /emr/care_plan/print/{emr_id}` → `CarePlanController@print`.
- View `resources/views/moduls/EMR/CarePlan/print.blade.php`.
- Isi: kop + judul **RENCANA ASUHAN KEPERAWATAN (CARE PLAN)**, blok
  "Diagnosa → Care Plan → Non Farmakologis → Farmakologis", blok **TARGET
  TERUKUR** (perkiraan lama rawat / target perawatan / kriteria pemulangan),
  tanggal evaluasi, blok **NAMA DAN TANDA TANGAN TIM YANG HADIR** (DPJP utama,
  DPJP konsultan, tim perawatan), dan baris tanda tangan.

---

## 5. Form 21 — DAR (D-Rekognisi)

### 5.1 Rasional & tujuan klinis

DAR adalah catatan **D-Rekognisi** — frequently di Indonesia berarti catatan
kejadian untuk keperluan hukum/forensik yang dibuat petugas kesehatan saat
menangani pasien (kecelakaan, kekerasan,_found_dead, atau pelaporanClinik).
Struktur legacy sangat ringkas: tiga textarea wajib — **Data**, **Aksi**,
**Respon** — ditambah header read-only hasil pengkajian awal keperawatan.

NusaMedika tidak punya form ini; karena itu PPA/tim hukum tidak punya jejak
tertulis di dalam sistem.

### 5.2 Struktur Field

| variabel | objek_id | nama_objek | tipe kontrol | wajib | keterangan |
|---|---|---|---|---|---|
| `tanggal_dar` | 196 | Tanggal DAR | date | ya | Default `today()`. |
| `waktu_dar` | 156 | Waktu Observasi | time (H:i) | ya | **Reuse objek 156.** |
| `data` | 2 | Objective (O) | textarea | ya | **Reuse objek 2.** Legacy `data` (objek 35). Uraian faktual kejadian. Popover "Dilarang Copy Paste / tidak menggunakan ' & ''". |
| `aksi` | 4 | Planning (P) | textarea | ya | **Reuse objek 4.** Legacy `aksi` (objek 36). Tindakan yang dilakukan petugas. |
| `respon` | 3 | Assessment (A) | textarea | ya | **Reuse objek 3.** Legacy `respon` (objek 3). Respon pasien/keluarga terhadap tindakan. |
| `keterangan` | 77 | Keterangan | textarea | tidak | **Reuse objek 77.** |

Panel **read-only** di atas form: `head_pasien_dalam_form()` +
`assesment_perawat_dalam_form()` dari legacy. Di NusaMedika diisi dari
`EmrHelper::latestValuesByVariabel()` atas form 3 (Pengkajian Awal Keperawatan)
atau form 19 (Triage IGD) pada `registrasi_detail` yang sama — variabel:
`keadaan_umum`/`ku`, `kesadaran`, `td_sistolik`, `td_diastolik`, `berat_badan`,
`tinggi_badan`, `suhu`, `nadi`, `keluhan_utama`, `risiko_jatuh`. Panel ini
**tidak punya mapping** — murni tampilan.

### 5.3 Dashboard & Form Master

Sudah ditulis di §3.3: sub-menu 15 (`'1.15.6'`) + extra 6.

```php
['dashboard_menu_sub_id' => 15, 'dashboard_menu_id' => 1, 'nama_sub_menu' => 'Catatan Medis Visum'],
['dashboard_menu_sub_extra_id' => 6, 'dashboard_menu_sub_id' => 15, 'nama_sub_menu_extra' => 'DAR'],

['form_id' => 21, 'nama_form' => 'DAR', 'slug' => 'dar', 'id_dash_menu' => '1.15.6', 'ri' => 1, 'rj' => 0, 'igd' => 1, 'mcu' => 0],
['form_id' => 57, 'nama_form' => 'Catatan Medis Visum', 'slug' => 'catatan_medis_visum', 'id_dash_menu' => '1.15', 'ri' => 0, 'rj' => 0, 'igd' => 1, 'mcu' => 0],
```

### 5.4 Mapping

```php
$mapping[21] = [
    'tanggal_dar' => 196,
    'waktu_dar'   => 156,  // reuse "Waktu Observasi"
    'data'        => 2,    // reuse "Objective (O)"
    'aksi'        => 4,    // reuse "Planning (P)"
    'respon'      => 3,    // reuse "Assessment (A)"
    'keterangan'  => 77,   // reuse "Keterangan"
];
```

### 5.5 Akses EHR

```php
['profesi_id' => 1, 'form_id' => 21, 'level_id' => 1, 'bagian_id' => null, 'akses_create' => 1, 'akses_read' => 1, 'akses_update' => 1, 'akses_delete' => 1],
['profesi_id' => 2, 'form_id' => 21, 'level_id' => 1, 'bagian_id' => null, 'akses_create' => 0, 'akses_read' => 1, 'akses_update' => 0, 'akses_delete' => 0],
```

### 5.6 Validasi

```php
[
    'tanggal_dar' => 'required|date',
    'waktu_dar'   => 'required|date_format:H:i',
    'data'        => 'required|string|max:3000',
    'aksi'        => 'required|string|max:3000',
    'respon'      => 'required|string|max:3000',
    'keterangan'  => 'nullable|string|max:1000',
]
```

Tambahan: `abort_unless(AksesEhr::can($formId, 'create'), 403)` di `store()`
mencegah perawat membuat DAR walau `akses_create` diubah lewat UI Akses EHR —
`AksesEhr::can()` sudah cukup, tidak perlu guard tambahan.

### 5.7 Cetak

**Opsional (fase 2).** Legacy tidak punya print dedicated; DAR ditampilkan di
panel history. Bila dibutuhkan, buat `print.blade.php` dengan header
`head_pasien_dalam_form()` + tiga blok Data/Aksi/Respon + blok tanda tangan.
Prioritas rendah karena DAR jarang diminta ulang.

---

## 6. Form 57 — Catatan Medis Visum

### 6.1 Rasional & tujuan klinis

Visum et repertum adalah **surat keterangan medico-legal** yang dibuat dokter atas
permintaan aparat penegak hukum (polisi / jaksa) untuk keperluan pembuktian.
Struktur dan redaksinya **teratur** (Undang-Undang Nomor 350 Tahun 1937),
sehingga layout cetak tidak boleh diubah sembarangan.

NusaMedika belum punya tempat penyimpanan visum, sehingga permintaan dari
penyidik harus ditangani manual di luar sistem.

### 6.2 Struktur Field

| variabel | objek_id | nama_objek | tipe kontrol | wajib | keterangan |
|---|---|---|---|---|---|
| `tanggal_visum` | 155 | Tanggal Observasi | date (readonly) | ya | **Reuse objek 155.** Tanggal pemeriksaan bukan tanggal pencetakan. Default `today()`, tidak dapat diubah (visum mencantumkan tanggal pemeriksaan, bukan tanggal dibuat). |
| `waktu_visum` | 156 | Waktu Observasi | time (readonly) | ya | **Reuse objek 156.** |
| `jenis_visum` | 197 | Jenis Visum | select | ya | Legacy `jenis_visum` (objek 796): `LAKA / KLL`, `KDRT`, `LAIN-LAIN`. **SelectOption key baru: `jenis_visum`.** |
| `nomor_visum` | 198 | Nomor Visum Et Repertum | text readonly | ya | Legacy `kode_visum` dari tabel `urut_visum`. **Dibangkitkan server** saat `store()` — `"V/" . $registrasi_detail_id . "/" . $registrasi_detail_id . "/" . str_pad($urut, 3, '0', STR_PAD_LEFT) . "/{$tahun}"`. Tidak boleh diubah user. |
| `dokter_visum` | 139 | Dokter Pemeriksa | select | ya | **Reuse objek 139.** Legacy `dokter_visum` (objek 797) — daftar dokter (`pegawai` `profesi_id = 1`) yang terdaftar sebagai 'dokter pemeriksa'. Disimpan `pegawai_id` (bukan `user_id` seperti legacy). |
| `permintaan_visum` | 199 | Permintaan Visum | text | ya | Legacy `permintaan_visum` (objek 798), contoh: "Permintaan Visum dari [INSTITUSI PEMOHON] — nama instansi pemohon diisi lengkap". |
| `benda_bukti_identifisir` | 77 | Keterangan | textarea | ya | **Reuse objek 77.** Legacy `benda_bukti_identifisir` (objek 799). |
| `hasil_visum` | 2 | Objective (O) | textarea | ya | **Reuse objek 2.** Legacy `hasil_visum` (objek 800). |
| `kesimpulan_visum` | 3 | Assessment (A) | textarea | ya | **Reuse objek 3.** Legacy `kesimpulan_visum` (objek 801). |
| `kelainan_sebab` | 1 | Subjective (S) | textarea | ya | **Reuse objek 1.** Legacy `kelainan_sebab` (objek 802) — "Luka-Luka/Kelainan tersebut disebabkan oleh karena". |
| `kelainan_akibat` | 4 | Planning (P) | textarea | ya | **Reuse objek 4.** Legacy `kelainan_akibat` (objek 803) — "Luka-Luka/Kelainan tersebut mengakibatkan". |

Field yang **dibuang** dari legacy:

| Field legacy | Alasan dibuang |
|---|---|
| `bagian_id` (objek 152) | Legacy Hard-code IGD. Di NusaMedika bagian diambil dari `registrasi_detail.bagian_id`, tidak disimpan per-visum. |

### 6.3 Dashboard & Form Master

Sudah ditulis di §3.3: sub-menu 15 (`'1.15'`).

```php
['dashboard_menu_sub_id' => 15, 'dashboard_menu_id' => 1, 'nama_sub_menu' => 'Catatan Medis Visum'],

['form_id' => 57, 'nama_form' => 'Catatan Medis Visum', 'slug' => 'catatan_medis_visum', 'id_dash_menu' => '1.15', 'ri' => 0, 'rj' => 0, 'igd' => 1, 'mcu' => 0],
```

> `form_id = 57` diambil dari
> [`ANALISIS_KEKURANGAN_FORM.md`](ANALISIS_KEKURUGAN_FORM.md) §3 P2 — nomornya
> dilompat agar sejajar dengan roadmap Fase 2. **Bukan** berarti form 22–56 sudah
> ada; lihat §8.

### 6.4 Mapping

```php
$mapping[57] = [
    'tanggal_visum'           => 155,  // reuse "Tanggal Observasi"
    'waktu_visum'             => 156,  // reuse "Waktu Observasi"
    'jenis_visum'             => 197,
    'nomor_visum'             => 198,
    'dokter_visum'            => 139,  // reuse "Dokter Pemeriksa"
    'permintaan_visum'        => 199,
    'benda_bukti_identifisir' => 77,   // reuse "Keterangan"
    'hasil_visum'             => 2,    // reuse "Objective (O)"
    'kesimpulan_visum'        => 3,    // reuse "Assessment (A)"
    'kelainan_sebab'          => 1,    // reuse "Subjective (S)"
    'kelainan_akibat'         => 4,    // reuse "Planning (P)"
];
```

### 6.5 Akses EHR

```php
// Visum: HANYA Dokter. Tidak ada baris untuk Perawat (2) sama sekali —
// sehingga form tidak muncul di dashboard perawat dan URL langsung 403.
['profesi_id' => 1, 'form_id' => 57, 'level_id' => 1, 'bagian_id' => null, 'akses_create' => 1, 'akses_read' => 1, 'akses_update' => 1, 'akses_delete' => 1],
```

> Menghapus visum (`delete`) **tidak diizinkan** setelah nomor terbit karena
> Visum adalah dokumen bermeterai. `akses_delete = 1` di baris di atas
> agar route generik tidak error; guard tambahan di `destroy()`:
> `abort_if($emr->input_time->diffInDays(now()) > 7, 403, 'Visum yang sudah lebih dari 7 hari tidak dapat dihapus.')`.
> Alternatif yang lebih bersih: set `akses_delete = 0` di seed **dan** beri
> `delete` hanya lewat menu Akses EHR untuk admin —putuskan saat implementasi.

### 6.6 Validasi

```php
[
    'jenis_visum'             => ['required', Rule::in(['LAKA / KLL', 'KDRT', 'LAIN-LAIN'])],
    'dokter_visum'            => ['required', 'integer', 'exists:pegawai,pegawai_id'],
    'permintaan_visum'        => 'required|string|max:1000',
    'benda_bukti_identifisir' => 'required|string|max:3000',
    'hasil_visum'             => 'required|string|max:5000',
    'kesimpulan_visum'        => 'required|string|max:5000',
    'kelainan_sebab'          => 'required|string|max:2000',
    'kelainan_akibat'         => 'required|string|max:2000',
]
```

| Field | Aturan |
|---|---|
| `tanggal_visum`, `waktu_visum` | Di-set dari `Carbon::now()` di `filteredData()`; nilai dari browser **tidak dipakai**. |
| `nomor_visum` | Dibangkitkan di `filteredData()` bila `store()` (kosong) atau `update()` (pertahankan nilai lama dari `emr_detail`). Nomor **tidak pernah berubah** setelah terbit. |
| `dokter_visum` | Wajib `pegawai.profesi_id = 1` (dokter) — validasi `Rule::exists('pegawai','pegawai_id')->where('profesi_id', 1)`. |

### 6.7 Cetak

**Wajib.** Layout harus mengikuti `FE/lib/cetak/cetak_visum.php` persis karena
formatnya diatur undang-undang:

1. Kop rumah sakit (`kop_vector.php` legacy → logo + alamat + kontak).
2. Judul **P R O J U S T I T I A** (spasi lebar, rata tengah).
3. Tabel `Jenis`, `Visum Et Repertum No`.
4. Paragraf redaksi: *"Saya yang bertanda tangan dibawah ini, **{nama_dokter}**, dokter bagian **{nama_bagian}**, menyatakan bahwa pada tanggal : **{tanggal} WIB**, saya telah melakukan pemeriksaan atas benda bukti :"*
5. Tabel identitas benda bukti: No MR, Nama Pasien, Bangsa, Jenis Kelamin, Umur, Tempat Tinggal.
6. `Berhubung permintaan :` dan `Benda bukti telah diidentifisir dengan`.
7. Heading **HASIL PEMERIKSAAN** → isi `hasil_visum`.
8. Heading **KESIMPULAN** → isi `kesimpulan_visum`.
9. Dua baris: `Luka-Luka/Kelainan tersebut disebabkan oleh karena` dan `... mengakibatkan`.
10. Penutup: *"Demikian saya uraikan sejujur-jujurnya atas sumpah dokter sesuai lembar Negara 1937 No. 350 untuk dipergunakan dimana perlu."*
11. Blok tanda tangan: namaunit, "Dokter tersebut diatas", garis bawah, nama dokter.

Route: `GET /emr/catatan_medis_visum/print/{emr_id}` →
`CatatanMedisVisumController@print`.

---

## 7. Implementasi

Checklist file yang harus dibuat per form. **Tidak ada migration baru** dan
**tidak ada route manual** selain route `print` (PANDUAN §1).

### 7.1 Form 16 — Resume Medis

- [ ] `database/seeders/EmrMasterSeeder.php` — tambahkan baris sub-menu 13, objek 178–188, `$mapping[16]`, 2 baris `$akses`.
- [ ] `app/Helpers/EmrHelper.php` — tidak ada perubahan; panggil `EmrHelper::backfillObjekId(16)` di seeder.
- [ ] `app/Helpers/SelectOption.php` — tidak ada key baru (pakai `alasan_pulang` yang sudah ada).
- [ ] `app/Http/Controllers/EMR/ResumeMedis/ResumeMedisController.php` — `index/store/update/destroy/print`, plus `ringkasanPenunjang()`, `ringkasanTerapi()`, `ringkasanLaporanOperasi()`, `ringkasanBedLog()`, `ringkasanKontrol()` sebagai query read-only.
- [ ] `resources/views/moduls/EMR/ResumeMedis/index.blade.php` — 6 accordion: Kondisi Awal, Tanda Vital & GCS, Pemeriksaan Fisik, Diagnosa Resume, Kondisi Pulang, Data Turunan.
- [ ] `resources/views/moduls/EMR/ResumeMedis/print.blade.php` — 10 section (§3.7).
- [ ] `resources/views/moduls/EMR/PartialForm/pemeriksaan_fisik.blade.php` — **opsional**, sudah ada partial `pemeriksaan_fisik` di `moduls/EMR/PartialForm/`; sesuaikan nama variabelnya ke `pf_*`.
- [ ] `routes/web.php` — `Route::get('/emr/resume_medis/print/{emr_id}', [ResumeMedisController::class,'print'])->name('emr.resume_medis.print');`

### 7.2 Form 20 — Care Plan

- [ ] `EmrMasterSeeder.php` — sub-menu 14, objek 189–195, `$mapping[20]`, 2 baris `$akses`.
- [ ] `app/Http/Controllers/EMR/CarePlan/CarePlanController.php` — `index/store/update/destroy/print`.
- [ ] `resources/views/moduls/EMR/CarePlan/index.blade.php` — accordion: Identitas & Waktu, Diagnosa & Care Plan, Intervensi, Target Terukur, Tim yang Hadir.
- [ ] `resources/views/moduls/EMR/CarePlan/print.blade.php`.
- [ ] `routes/web.php` — `emr.care_plan.print`.

### 7.3 Form 21 — DAR

- [ ] `EmrMasterSeeder.php` — objek 196, extra 6, `$mapping[21]`, 2 baris `$akses`.
- [ ] `app/Http/Controllers/EMR/Dar/DarController.php` — `index/store/update/destroy`.
- [ ] `resources/views/moduls/EMR/Dar/index.blade.php` — panel read-only pengkajian awal + 3 textarea.
- [ ] Route: **tidak perlu**, generic route EMR sudah menangani.

### 7.4 Form 57 — Catatan Medis Visum

- [ ] `EmrMasterSeeder.php` — objek 197–199, `$mapping[57]`, 1 baris `$akses`.
- [ ] `app/Helpers/SelectOption.php` — key baru `jenis_visum`.
- [ ] `app/Helpers/VisumHelper.php` — **helper baru**, method `nomorBerikutnya(RegistrasiDetail $rd): string` untuk bangkitkan `V/{registrasi_id}/{registrasi_detail_id}/{urut}/{tahun}`. Mengulang counters + `DB::transaction` + `lockForUpdate()`.
- [ ] `app/Http/Controllers/EMR/CatananMedisVisum/CatatanMedisVisumController.php` — **WAJIB nama folder persis**; `Str::studly('catatan_medis_visum')` = `CatananMedisVisum` dengan typo. **Perbaiki jadi `CatatanMedisVisum`** dan pastikan slug tetap `catatan_medis_visum` (folder ≠ slug, yang penting `Str::studly(slug)`).
- [ ] `resources/views/moduls/EMR/CatatanMedisVisum/index.blade.php`.
- [ ] `resources/views/moduls/EMR/CatatanMedisVisum/print.blade.php`.
- [ ] `routes/web.php` — `emr.catatan_medis_visum.print`.

### 7.5 Review sebelum merge (PANDUAN §4)

- [ ] `Str::slug('Resume Medis','_') === 'resume_medis'`, `Str::slug('Care Plan','_') === 'care_plan'`, `Str::slug('Catatan Medis Visum','_') === 'catatan_medis_visum'`, `Str::slug('DAR','_') === 'dar'`.
- [ ] `id_dash_menu` `'1.13'`, `'1.14'`, `'1.15.6'`, `'1.15'` cocok dengan PK yang benar-benar di-seed.
- [ ] Semua `variabel` unik per form (termasuk 12 `pf_*` dan 10 `tim_perawatan`).
- [ ] `array_intersect_key()` di `filteredData()` — tidak ada data_gateway yang lolos.
- [ ] Field computed (`total_ews`, `kategori_ews`, `gcs_jumlah`, `nomor_visum`) dihitung/dibangkitkan ulang di server.
- [ ] `dpjp_utama`, `dokter_visum` diisi dari master, bukan dari browser.
- [ ] `@error()` ada di setiap field; `{{ $isView ? 'disabled' : '' }}` ada di `<fieldset>`; `old('x', $emr_data['x'] ?? '')` dipakai.
- [ ] `docker compose exec app ./vendor/bin/pint --dirty`.
- [ ] Entri `AGENTS.md` diperbarui.

---

## 8. Catatan & Risiko

### 8.1 Risiko alokasi objek

| Risiko | Mitigasi |
|---|---|
| Objek 178–199 dipakai dokumen lain secara tidak sengaja. | Semua dokumen konsep mengalaminya di rentang eksplisit. Section §1 dokumen ini menyatakan batas atas **199**. Jalankan `php artisan tinker` → `DB::table('objek')->where('objek_id','>',177)->orderBy('objek_id')->get()` sebelum menjalankan seeder. |
| `pf_kepala`..`pf_ekstremitas` 12 variabel → 1 objek. | during baca harus **tidak** memakai `EmrHelper::emrDetailByObjek()` (menghilang jadi 1 nilai). Gunakan `emrDetailByVariabel()`. Laporan antar form yang berbasis objek akan melihat 12 baris dengan `objek_id = 188` — itu memang disengaja. |
| Objek `tanggal_carbon` dipakai bersama antar form (155/156/139/85/82/77/40/13/41/42/14/140/142/143/54–57/6/7/10/11/12). | Ini memang tujuan reuse — laporan antar form konsisten. Konsekuensi: query yang mengambil "Tanggal Observasi terakhir" harus selalu memfilter `form_id`, bukan global. |

### 8.2 Risiko menu

| Risiko | Mitigasi |
|---|---|
| `form.slug` tidak sama dengan nama sub/extra → form yatim. | Sudah didokumentasikan di §3.3. Verifikasi dengan `Str::slug()` saat review. |
| Sub 15 punya dua leaf (langsung + extra). | `header_ehr` menghasilkan baris `"1.15"` (tanpa extra) dan `"1.15.6"` (dengan extra). Blade `header_ehr` hanya bisa me-render link bila leaf punya extra **atau** slug match. **Wajib diuji manual** di dashboard pasien. |
| Perbandingan `id_dash_menu` dengan `==`. | Semua closure di `AksesEhrController` sudah `===` (fix sebelumnya). Jangan menambahkan `firstWhere('id_dash_menu', '1.15')` — `Collection::firstWhere()` memakai `==`. |

### 8.3 Risiko klinis & legal

| Risiko | Mitigasi |
|---|---|
| Visum salah hapus / nomor bentrok. | `VisumHelper::nomorBerikutnya()` memakai `lockForUpdate()` dalam transaksi. `destroy()` dibatasi 7 hari atau `akses_delete = 0`. |
| `tanggal_visum` diisi manual bisa berbeda dari waktu pembuatan. | Field di-set server dari `Carbon::now()`, input dikunci `readonly` di blade. |
| Resume diisi tidak lengkap tapi tetap bisa disimpan. | Wajib pada 11 field inti; section read-only boleh kosong (menampilkan "Belum ada data"). |
| Copy-paste narasi harboring `'` dan `&`. | Legacy sempat menandai hal ini dengan popover admonition. Di Laravel ini aman karena `{{ }}` auto-escape dan `EmrHelper` tidak melakukan `htmlentities` ganda. Tetap tampilkan popover peringatan di blade. |

### 8.4 Risiko performa

| Risiko | Mitigasi |
|---|---|
| Halaman resume memuat 6 query agregat read-only (lab, rad, resep, operasi, bed log, kontrol). | Gunakan `EmrHelper::latestValuesByVariabel()` / query agregat dengan `whereIn('registrasi_id', $episodeIds)` + `limit`. Cache per episode bila profiling menunjukkan masalah. |
| Tabel `objek` tumbuh ke ± 500 baris. | administrator → Manajemen EMR → Form sudah menyediakan tab Objek; tambahkan filter per menu bila `Administrator/ManajemenEMR/Form/objek` terasa lambat. |

### 8.5 Yang **tidak** dicakup dokumen ini

| Topik | destined untuk |
|---|---|
| Resume Keperawatan (`resume_keperawatan.php`) | `KONSEP_ASUHAN_KEPERAWATAN_LANJUTAN.md` |
| Care Plan HD (`care_plan_hd.php`) | `KONSEP_ICU_HEMODIALISA.md` |
| Laporan Operasi (`laporan_resume` / form 92) | `KONSEP_BEDAH_ANESTESI.md` |
| Transfer pasien IGD → ruang (`transfer_pasien_dari_igd_ke_ruangan.php`) | `KONSEP_BEDAH_ANESTESI.md` (form 94) |
| Form 22–56 yang bernomor di ANALISIS §3 | Dokumen konsep masing-masing (P1/P2/P3/P4) |

> **Catatan nomor form 57.** `form_id = 57` diallocate sekarang walau form 22–56
> belum ada — sesuai_master plan `ANALISIS_KEKURANGAN_FORM.md` §3 P2. Sourceless
> seeder idempoten (`updateOrInsert` berbasis PK) sehingga form 57 bisa di-seed
> lebih dulu tanpa mengunci nomor bagi dokumen lain.
