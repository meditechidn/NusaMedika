# Alokasi ID Modul Billing

Catatan: `sub_menu_id` baru mulai 66. Jangan menomori ulang sub_menu yang sudah
ada. Setelah record modul/menu/sub_menu dibuat, jalankan:

```bash
docker compose exec app php artisan seeder:sync-master-menu
```

## Modul
| ID | Nama | Icon | Urutan |
|---|---|---|---|
| 11 | Billing | `fa-solid fa-file-invoice-dollar` | 11 |

## Menu
| ID | Modul | Nama | Urutan |
|---|---|---|---|
| 20 | 11 | Transaksi | 1 |

## Sub Menu
| ID | Menu | Nama | file_sub_menu | Controller |
|---|---|---|---|---|
| 66 | 20 | Daftar Billing | `Billing/Transaksi/DaftarBilling/daftar_billing` | `Billing\Transaksi\DaftarBilling\DaftarBillingController` |
| 67 | 20 | Billing Pasien | `Billing/Transaksi/BillingPasien/billing_pasien` | `Billing\Transaksi\BillingPasien\BillingPasienController` |

Sub menu 68 (Kasir), 69 (Tutup Billing), dan 78 (Pengaturan Billing) sudah dihapus. Menu 21 (Jasa
Medis), 22 (Laporan), dan sub menu 70–75, 77, 79 sudah dihapus/dinonaktifkan.
Menu 23 (Manajemen Billing) dan sub menu 76 (Master Tarif Billing) juga sudah
dihapus — tarif kini dikelola di **Administrator → Manajemen Master → Master Tarif**.

## Route manual
- `GET  /billing_pasien/harga`
- `GET  /billing_pasien/cetak-episode`
- `GET  /billing_pasien/cetak-pasien`
- `POST /billing_pasien/tutup-semua`
- `POST /billing_pasien/buka-semua`
- `POST /billing_pasien/{billing}/item`
- `POST /billing_pasien/{billing}/item/{detail}/void`
- `POST /billing_pasien/{billing}/diskon`
- `POST /billing_pasien/{billing}/tutup`
- `POST /billing_pasien/{billing}/buka`
- `POST /billing_pasien/{billing}/void`
- `GET  /billing_pasien/{billing}/cetak`

Semua parameter id wajib `->whereNumber()` agar tidak menabrak auto-route.

## Tabel
Transaksi: `billing`, `billing_detail`.

Master tarif: `tindakan`, `tarif` (satu baris per tindakan per kelas
perawatan + baris default `kelas_ruang_id` NULL).

Tabel pembayaran/deposit/piutang/penjamin/log/counter/pengaturan/tenaga,
kasir shift, paket tindakan, jasa medis, dan klaim sudah dihapus.
