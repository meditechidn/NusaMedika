<?php

namespace App\Helpers;

use App\Models\BatchBarang;
use App\Models\KartuStock;
use App\Models\Stock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class StockHelper
{
    /**
     * Mencatat stok masuk: update saldo tabel `stock` + tulis baris kartu_stock.
     *
     * @param  array{jenis_mutasi?: int, ref_penerimaan_detail_id?: int|null, ref_mutasi_barang_detail_id?: int|null, ref_peresepan_obat_detail_id?: int|null, tanggal?: \DateTimeInterface|Carbon|null, keterangan?: string|null, harga_beli?: float|null, harga_jual?: float|null, tgl_expired?: string|null}  $opts
     */
    public static function tambahMasuk(int $bagianId, int $barangId, ?string $noBatch, float $qty, array $opts = []): void
    {
        self::catat($bagianId, $barangId, $noBatch, $qty, 0, $opts);
    }

    /**
     * Mencatat stok keluar: update saldo tabel `stock` + tulis baris kartu_stock.
     *
     * @param  array{jenis_mutasi?: int, ref_penerimaan_detail_id?: int|null, ref_mutasi_barang_detail_id?: int|null, ref_peresepan_obat_detail_id?: int|null, tanggal?: \DateTimeInterface|Carbon|null, keterangan?: string|null, harga_beli?: float|null, harga_jual?: float|null, tgl_expired?: string|null}  $opts
     */
    public static function tambahKeluar(int $bagianId, int $barangId, ?string $noBatch, float $qty, array $opts = []): void
    {
        self::catat($bagianId, $barangId, $noBatch, 0, $qty, $opts);
    }

    private static function catat(int $bagianId, int $barangId, ?string $noBatch, float $masuk, float $keluar, array $opts): void
    {
        $hargaBeli = isset($opts['harga_beli']) ? (float) $opts['harga_beli'] : null;
        $hargaJual = isset($opts['harga_jual']) ? (float) $opts['harga_jual'] : null;
        $tglExpired = $opts['tgl_expired'] ?? null;

        // Cari/buat batch_barang (per barang + no_batch) untuk menyimpan harga.
        $batch = BatchBarang::aktif()
            ->where('barang_id', $barangId)
            ->where('no_batch', $noBatch)
            ->first();

        if (! $batch) {
            $batch = new BatchBarang;
            $batch->barang_id = $barangId;
            $batch->no_batch = $noBatch;
            $batch->harga_beli = $hargaBeli ?? 0;
            $batch->harga_jual = $hargaJual ?? 0;
            $batch->tgl_expired = $tglExpired;
            $batch->input_time = now();
            $batch->input_user_id = Auth::id();
            $batch->status_batal = 0;
            $batch->save();
        } else {
            if ($hargaBeli !== null) {
                $batch->harga_beli = $hargaBeli;
            }
            if ($hargaJual !== null) {
                $batch->harga_jual = $hargaJual;
            }
            if ($tglExpired !== null) {
                $batch->tgl_expired = $tglExpired;
            }
            $batch->mod_time = now();
            $batch->mod_user_id = Auth::id();
            $batch->save();
        }

        $stock = Stock::aktif()
            ->where('barang_id', $barangId)
            ->where('bagian_id', $bagianId)
            ->where('no_batch', $noBatch)
            ->where('id_batch', $batch->id_batch)
            ->first() ?? new Stock;

        if (! $stock->exists) {
            $stock->barang_id = $barangId;
            $stock->no_batch = $noBatch;
            $stock->bagian_id = $bagianId;
            $stock->id_batch = $batch->id_batch;
            $stock->input_time = now();
            $stock->input_user_id = Auth::id();
            $stock->status_batal = 0;
        }

        $sebelum = (float) ($stock->jumlah ?? 0);
        $sesudah = $sebelum + $masuk - $keluar;
        $stock->jumlah = $sesudah;
        $stock->mod_time = now();
        $stock->mod_user_id = Auth::id();
        $stock->save();

        self::tulisKartu($bagianId, $barangId, $noBatch, $masuk, $keluar, $sebelum, $sesudah, $opts, (float) $batch->harga_beli, (float) $batch->harga_jual, $tglExpired);
    }

    private static function tulisKartu(
        int $bagianId,
        int $barangId,
        ?string $noBatch,
        float $masuk,
        float $keluar,
        float $sebelum,
        float $sesudah,
        array $opts,
        ?float $hargaBeli = null,
        ?float $hargaJual = null,
        ?string $tglExpired = null,
    ): void {
        $kartu = new KartuStock;
        $kartu->barang_id = $barangId;
        $kartu->no_batch = $noBatch;
        $kartu->bagian_id = $bagianId;
        $kartu->tanggal = $opts['tanggal'] ?? now();
        $kartu->jenis_mutasi = $opts['jenis_mutasi'] ?? ($masuk > 0 ? 1 : 3);
        $kartu->ref_penerimaan_detail_id = $opts['ref_penerimaan_detail_id'] ?? null;
        $kartu->ref_mutasi_barang_detail_id = $opts['ref_mutasi_barang_detail_id'] ?? null;
        $kartu->ref_peresepan_obat_detail_id = $opts['ref_peresepan_obat_detail_id'] ?? null;
        $kartu->ref_emr_id = $opts['ref_emr_id'] ?? null;
        $kartu->qty_masuk = $masuk;
        $kartu->qty_keluar = $keluar;
        $kartu->saldo_sebelum = $sebelum;
        $kartu->saldo_sesudah = $sesudah;
        $kartu->harga_beli = $hargaBeli;
        $kartu->harga_jual = $hargaJual;
        $kartu->tgl_expired = $tglExpired;
        $kartu->keterangan = $opts['keterangan'] ?? null;
        $kartu->input_time = now();
        $kartu->input_user_id = Auth::id();
        $kartu->status_batal = 0;
        $kartu->save();
    }

    /**
     * Sesuaikan saldo stok satu baris dengan hasil hitung fisik (Stock Opname).
     *
     * Selisih dicatat di `kartu_stock` sebagai jenis mutasi 6 (Penyesuaian Stock
     * Opname) sehingga riwayat koreksi tetap terlacak. Mengembalikan null bila
     * baris tidak ditemukan/tidak aktif atau jumlah fisik sama dengan sistem.
     *
     * @param  array{jenis_mutasi?: int, tanggal?: \DateTimeInterface|Carbon|null, keterangan?: string|null}  $opts
     * @return array{sebelum: float, sesudah: float}|null
     */
    public static function sesuaikan(int $stockId, float $fisik, array $opts = []): ?array
    {
        if ($fisik < 0) {
            throw new InvalidArgumentException('Jumlah stok fisik tidak boleh negatif.');
        }

        $stock = Stock::aktif()->lockForUpdate()->find($stockId);
        if (! $stock) {
            return null;
        }

        $sebelum = (float) ($stock->jumlah ?? 0);
        if (abs($sebelum - $fisik) < 0.00001) {
            return null;
        }

        $stock->jumlah = $fisik;
        $stock->mod_time = now();
        $stock->mod_user_id = Auth::id();
        $stock->save();

        self::tulisKartu(
            (int) $stock->bagian_id,
            (int) $stock->barang_id,
            $stock->no_batch,
            max(0.0, $fisik - $sebelum),
            max(0.0, $sebelum - $fisik),
            $sebelum,
            $fisik,
            array_merge([
                'jenis_mutasi' => KartuStock::JENIS_PENYESUAIAN,
                'keterangan' => 'Stock Opname',
            ], $opts),
            $stock->batchBarang ? (float) $stock->batchBarang->harga_beli : null,
            $stock->batchBarang ? (float) $stock->batchBarang->harga_jual : null,
            $stock->batchBarang && $stock->batchBarang->tgl_expired ? substr((string) $stock->batchBarang->tgl_expired, 0, 10) : null,
        );

        return ['sebelum' => $sebelum, 'sesudah' => $fisik];
    }

    /**
     * Hapus satu baris stok (soft delete) dengan catatan audit bila masih bersaldo.
     *
     * @param  array{keterangan?: string|null, tanggal?: \DateTimeInterface|Carbon|null}  $opts
     * @return array{sebelum: float}|null
     */
    public static function hapus(int $stockId, array $opts = []): ?array
    {
        $stock = Stock::aktif()->lockForUpdate()->find($stockId);
        if (! $stock) {
            return null;
        }

        $sebelum = (float) ($stock->jumlah ?? 0);

        if (abs($sebelum) > 0.00001) {
            self::tulisKartu(
                (int) $stock->bagian_id,
                (int) $stock->barang_id,
                $stock->no_batch,
                0.0,
                $sebelum,
                $sebelum,
                0.0,
                array_merge([
                    'jenis_mutasi' => KartuStock::JENIS_PENYESUAIAN,
                    'keterangan' => 'Hapus stock (Stock Opname)',
                ], $opts),
                $stock->batchBarang ? (float) $stock->batchBarang->harga_beli : null,
                $stock->batchBarang ? (float) $stock->batchBarang->harga_jual : null,
                $stock->batchBarang && $stock->batchBarang->tgl_expired ? substr((string) $stock->batchBarang->tgl_expired, 0, 10) : null,
            );
        }

        $stock->status_batal = 1;
        $stock->jumlah = 0;
        $stock->mod_time = now();
        $stock->mod_user_id = Auth::id();
        $stock->save();

        return ['sebelum' => $sebelum];
    }

    /**
     * Daftar batch yang tersedia pada satu bagian/lokasi.
     *
     * Dipakai form EMR Tindakan Medis untuk mengisi dropdown nomor batch, badge
     * sisa stok, dan tabel batch di modal "Tambah Obat/BMHP". Sumbernya tabel
     * `stock` (running balance), bukan penjumlahan ulang dari penerimaan/mutasi.
     *
     * Bentuk balik:
     *   [barang_id => [
     *       'BATCH-001' => ['jumlah' => 50.0, 'tgl_expired' => '2027-10-01', 'kedaluwarsa' => false],
     *   ], ...]
     *
     * Hanya batch dengan saldo > 0 yang disertakan, dan diurutkan **FEFO**
     * (tgl_expired terdekat lebih dulu) supaya batch yang hampir habis dipakai
     * lebih dahulu. Batch tanpa `tgl_expired` diletakkan di akhir.
     */
    public static function sisaPerBatch(int $bagianId): array
    {
        $rows = Stock::query()
            ->join('batch_barang', 'batch_barang.id_batch', '=', 'stock.id_batch')
            ->where(function ($q) {
                $q->whereNull('stock.status_batal')->orWhere('stock.status_batal', 0);
            })
            ->where('stock.bagian_id', $bagianId)
            ->where('stock.jumlah', '>', 0)
            ->get(['stock.barang_id', 'stock.no_batch', 'stock.jumlah', 'batch_barang.tgl_expired as tgl_expired']);

        $result = [];
        foreach ($rows as $row) {
            $noBatch = (string) $row->no_batch;

            if (! isset($result[$row->barang_id][$noBatch])) {
                $expired = $row->tgl_expired
                    ? substr((string) $row->tgl_expired, 0, 10)
                    : null;

                $result[$row->barang_id][$noBatch] = [
                    'jumlah' => 0.0,
                    'tgl_expired' => $expired,
                    'kedaluwarsa' => $expired !== null && $expired < now()->format('Y-m-d'),
                ];
            }

            $result[$row->barang_id][$noBatch]['jumlah'] += (float) $row->jumlah;

            // Bila satu batch punya beberapa baris stok dengan tanggal berbeda,
            // pakai yang paling awal agar tidak pernah dipakai lewat tanggal.
            $expired = $row->tgl_expired ? substr((string) $row->tgl_expired, 0, 10) : null;
            if ($expired !== null && ($result[$row->barang_id][$noBatch]['tgl_expired'] === null || $expired < $result[$row->barang_id][$noBatch]['tgl_expired'])) {
                $result[$row->barang_id][$noBatch]['tgl_expired'] = $expired;
                $result[$row->barang_id][$noBatch]['kedaluwarsa'] = $expired < now()->format('Y-m-d');
            }
        }

        // Urutan FEFO: tanggal kedaluwarsa terdekat lebih dulu.
        foreach ($result as $barangId => $batches) {
            unset($batches);

            uasort($result[$barangId], function ($a, $b) {
                if ($a['tgl_expired'] === $b['tgl_expired']) {
                    return 0;
                }

                // Batch tanpa tanggal expired dibiarkan di akhir.
                if ($a['tgl_expired'] === null) {
                    return 1;
                }

                if ($b['tgl_expired'] === null) {
                    return -1;
                }

                return $a['tgl_expired'] <=> $b['tgl_expired'];
            });
        }

        return $result;
    }

    /**
     * Apakah nomor batch suatu barang sudah lewat tanggal kedaluwarsa.
     *
     * Mengembalikan true bila batch tidak ada, atau tanggal expired paling awal
     * di antara baris stoknya sudah lewat.
     */
    public static function batchKedaluwarsa(int $bagianId, int $barangId, ?string $noBatch): bool
    {
        if ($noBatch === null || $noBatch === '') {
            return true;
        }

        $expired = Stock::query()
            ->join('batch_barang', 'batch_barang.id_batch', '=', 'stock.id_batch')
            ->where(function ($q) {
                $q->whereNull('stock.status_batal')->orWhere('stock.status_batal', 0);
            })
            ->where('stock.bagian_id', $bagianId)
            ->where('stock.barang_id', $barangId)
            ->where('stock.no_batch', $noBatch)
            ->whereNotNull('batch_barang.tgl_expired')
            ->min('batch_barang.tgl_expired');

        if ($expired === null) {
            return false;
        }

        return substr((string) $expired, 0, 10) < now()->format('Y-m-d');
    }

    /**
     * Sisa stok satu nomor batch. Mengembalikan 0.0 bila batch tidak ada.
     */
    public static function sisaBatch(int $bagianId, int $barangId, ?string $noBatch): float
    {
        if ($noBatch === null || $noBatch === '') {
            return 0.0;
        }

        return (float) Stock::aktif()
            ->where('bagian_id', $bagianId)
            ->where('barang_id', $barangId)
            ->where('no_batch', $noBatch)
            ->sum('jumlah');
    }

    /**
     * Catat pemakaian obat/BMHP dari form EMR Tindakan Medis.
     *
     * Memakai `jenis_mutasi` 4 (Pemakaian) pada kartu_stock, sehingga stok
     * pada bagian tempat pasien dirawat berkurang.
     */
    public static function catatPemakaian(int $bagianId, int $barangId, string $noBatch, float $qty, array $opts = []): void
    {
        self::tambahKeluar($bagianId, $barangId, $noBatch, $qty, array_merge([
            'jenis_mutasi' => KartuStock::JENIS_PEMAKAIAN,
            'keterangan' => 'Pemakaian - Tindakan Medis',
        ], $opts));
    }

    /**
     * Kembalikan stok pemakaian (untuk pembatalan/pengubahan data EMR).
     *
     * Dipakai saat data Tindakan Medis diubah atau dihapus: stok batch lama
     * dikembalikan dulu, lalu stok batch baru dicatat keluar.
     */
    public static function kembalikanPemakaian(int $bagianId, int $barangId, string $noBatch, float $qty, array $opts = []): void
    {
        self::tambahMasuk($bagianId, $barangId, $noBatch, $qty, array_merge([
            'jenis_mutasi' => KartuStock::JENIS_PEMAKAIAN,
            'keterangan' => 'Koreksi - Tindakan Medis',
        ], $opts));
    }
}
