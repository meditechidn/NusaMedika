# Konsep Modul Billing

Dokumen acuan modul Billing SIMRS NusaMedika. Modul ini sengaja dibuat
sederhana: hanya mencatat tagihan dan menutup/membuka billing. Tidak ada
pembayaran, deposit, piutang, penjamin, kasir, shift, jasa medis, klaim BPJS,
atau laporan di dalam modul Billing.

Semua nominal memakai `decimal(15,2)`. Tabel transaksi memakai kolom audit
`input_time`/`mod_time`/`input_user_id`/`mod_user_id` dan `status_batal`
(null/0 = aktif, 1 = batal).

## 1. Prinsip

1. **Satu billing per `registrasi_detail`.** Setiap tindakan/layanan pada satu
   detail registrasi (mis. POLI INTERNA) masuk ke satu `billing_id`. Satu
   `registrasi` dapat memiliki beberapa billing bila memiliki beberapa detail.
   **Tampilan** mengagregasi billing ke level **episode** (`referensi_registrasi_id`)
   dan mengelompokkan rincian per section `registrasi_detail` (POLI INTERNA,
   INSTALASI GAWAT DARURAT, LABORATORIUM, RADIOLOGI, RUANG PERAWATAN MAWAR, dst.).
   Biaya order penunjang dibebankan ke `registrasi_detail_tujuan_id`, bukan
   detail asal pemesan.
2. **Snapshot harga.** Perubahan master tarif tidak mengubah tagihan lama.
3. **Auto-charge idempoten** memakai `billing_detail.kode_unik` unik.
4. **Tanpa pembayaran.** `billing.dibayar`, `sisa`, pajak, service, materai,
   pembulatan, dan write-off selalu 0/kosong di logika baru.
5. **PostgreSQL**: gunakan `ilike`, `insertGetId(..., 'pk')`, `increments`,
   `COALESCE`. Jangan memakai `TIMESTAMPDIFF`/`IFNULL`/`GROUP_CONCAT`.

## 2. Status

### billing.status_billing
| Nilai | Status | Arti |
|---|---|---|
| 1 | OPEN | Terbuka, rincian masih bisa berubah |
| 3 | CLOSED | Ditutup |
| 9 | VOID | Dibatalkan |

`billing.status_selesai` = 1 saat ditutup, 0 saat dibuka kembali.

### billing_detail.status
| Nilai | Status |
|---|---|
| 0 | PENDING |
| 1 | POSTED |
| 2 | VOID |

## 3. Tabel

### Transaksi (hanya dua)
- `billing` — header per `registrasi_detail`.
- `billing_detail` — baris tagihan + snapshot harga/komponen.

### Master tarif
- `tindakan` — master tunggal; administrasi/kamar/makanan/visite/konsultasi/
  emergency/MCU dibedakan lewat `kategori_tindakan`.
- `tarif` — tarif per **(tindakan, kelas perawatan)** + baris default
  (`kelas_ruang_id` NULL) + komponen jasa sarana/medis/BHP. Kelas perawatan
  diambil dari master `kelas_ruang` (Kelas 1/2/3/VIP/VVIP).
- Harga barang **tidak** punya tabel tarif sendiri: memakai `stock`
  (`harga_beli`/`harga_jual` per batch).

## 4. Alur

```
Registrasi dibuat
  -> BillingHelper::pastikanBilling(registrasi, detail)
  -> syncAdministrasi (+ syncSuket untuk MCU)
  -> auto-charge:
       EMR tindakan/BMHP      : syncTindakanMedis
       resep dispense         : syncResepDispense
       order penunjang        : syncOrder (via PenunjangHelper)
       kamar rawat inap       : syncKamar
       konsultasi             : syncKonsultasi
  -> tambah item manual / diskon (halaman Billing Pasien)
  -> tutup semua billing episode -> CLOSED
  -> buka billing per billing    -> OPEN
  -> cetak episode / seluruh billing pasien
```

### Penomoran
- `no_billing`: `BILL/{Ymd}/{billing_id}` (diisi setelah insert).
- Tidak ada nomor closing; status `CLOSED` + `closed_at`/`closed_by` menjadi penanda penutupan.
- Tidak ada tabel counter; ID database menjadi urutan.

### Total
`subtotal` = jumlah `billing_detail.subtotal` berstatus POSTED.
`total` = `max(0, subtotal - diskon)`.
Kolom pajak/service/materai/pembulatan/dibayar/sisa penjamin diisi 0;
`sisa` = `total`, `tanggungan_pasien` = `total`.

## 5. Halaman

| Halaman | Fungsi |
|---|---|
| Daftar Billing | Daftar billing per **episode** kunjungan (RJ/IGD/RI), filter pasien/status/jenis/tanggal |
| Billing Pasien | Rincian episode per **section registrasi_detail**, tambah item, diskon, **tutup/buka semua billing episode**, buka/void per billing, cetak episode atau seluruh billing pasien |

Akses memakai `BillingAkses`: `DAFTAR_BILLING`, `BILLING_PASIEN`,
`MASTER_TARIF`. Tutup/buka billing memakai akses `BILLING_PASIEN`. Tarif tindakan dikelola di halaman
**Administrator → Manajemen Master → Master Tarif** (bukan halaman billing terpisah).

## 6. Yang sengaja tidak ada

Pembayaran, kwitansi, refund, deposit, piutang, penjamin, write-off, kasir,
shift kasir, jurnal/log billing, counter nomor, pengaturan pajak/service,
jasa medis, klaim BPJS/e-Klaim, dan laporan billing. Tabel terkait sudah
dihapus oleh migration `2026_10_27_000005_hapus_tabel_billing_pembayaran.php`.
