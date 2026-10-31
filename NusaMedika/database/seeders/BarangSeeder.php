<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Baseline Master Barang: katalog obat & BMHP.
 *
 * Diperlukan sebagai sumber dropdown pada:
 *  - Order Resep (EMR form 5)
 *  - Tindakan Medis (EMR form 10) — kolom "Pemakaian Obat/BMHP"
 *
 * `satuan_id` memakai baris `satuan` dari SatuanSeeder. Lookup satuan dilakukan
 * per NAMA (bukan id hardcode) agar aman bila id bergeser.
 *
 * Idempotent: updateOrInsert berdasarkan kode_barang.
 */
class BarangSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $jenisId = fn (string $nama) => DB::table('barang_jenis')
            ->where('nama_jenis_barang', $nama)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->orderBy('barang_jenis_id')
            ->value('barang_jenis_id');

        $satuanId = fn (string $nama) => DB::table('satuan')
            ->where('nama_satuan', $nama)
            ->value('satuan_id');

        $obat = $jenisId('Obat');
        $bmhp = $jenisId('BMHP');

        // [kode_barang, nama_barang, jenis, nama_satuan, kategori (0 Non Medis / 1 Medis)]
        $barangs = [
            ['OBT-001', 'Parasetamol 500 mg', $obat, 'Tablet', 1],
            ['OBT-002', 'Amoxicillin 500 mg', $obat, 'Kapsul', 1],
            ['OBT-003', 'Ibuprofen 400 mg', $obat, 'Tablet', 1],
            ['OBT-004', 'Cetirizin 10 mg', $obat, 'Tablet', 1],
            ['OBT-005', 'Oral Rehydration Salt Sachet', $obat, 'Sachet', 1],
            ['OBT-006', 'Ondansetron 4 mg/2 mL Injeksi', $obat, 'Ampul', 1],
            ['OBT-007', 'Natrium Klorida 0,9% 500 mL', $obat, 'Botol Infus', 1],
            ['OBT-008', 'Vitamin C 50 mg', $obat, 'Tablet', 1],

            ['BMH-001', 'Spuit 5 mL', $bmhp, 'Pcs', 0],
            ['BMH-002', 'Spuit 10 mL', $bmhp, 'Pcs', 0],
            ['BMH-003', 'Jarum Steril 25G', $bmhp, 'Pcs', 0],
            ['BMH-004', 'Kasa Steril 10 x 10 cm', $bmhp, 'Pcs', 0],
            ['BMH-005', 'Kain Kasa', $bmhp, 'Lembar', 0],
            ['BMH-006', 'Sarung Tangan M', $bmhp, 'Pasang', 0],
            ['BMH-007', 'Masker Medis', $bmhp, 'Pcs', 0],
            ['BMH-008', 'Handscoon', $bmhp, 'Pasang', 0],
            ['BMH-009', 'Infus Set', $bmhp, 'Pcs', 0],
            ['BMH-010', 'Alkohol 70% 100 mL', $bmhp, 'Botol', 0],
        ];

        foreach ($barangs as [$kode, $nama, $jenis, $satuan, $kategori]) {
            $satuanRow = $satuanId($satuan);

            DB::table('barang')->updateOrInsert(
                ['kode_barang' => $kode],
                [
                    'nama_barang' => $nama,
                    'jenis_barang_id' => $jenis,
                    'satuan_id' => $satuanRow ?: null,
                    'kategori_barang' => $kategori,
                    'is_racikan' => 0,
                    'is_fornas' => 0,
                    'input_time' => $now,
                    'input_user_id' => 1,
                    'status_batal' => 0,
                ]
            );
        }

        // Form EMR 111 (Double Check Obat High Alert): seluruh obat ditandai
        // high-alert sebagai prefill <datalist>. Linkage `peresepan_obat_dispense`
        // belum ada di skema ini, jadi sumbernya murni master barang.
        DB::table('barang')
            ->where('jenis_barang_id', $obat)
            ->update(['flag_alert' => 1]);
    }
}
