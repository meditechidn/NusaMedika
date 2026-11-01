<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Baseline Master Supplier & Distributor.
 *
 * Supplier (jenis_supplier = 'SUPPLIER') dan Distributor (jenis_supplier =
 * 'DISTRIBUTOR') disimpan di tabel yang sama `supplier`, dibedakan oleh
 * kolom `jenis_supplier`. Mapping:
 *   - barang_supplier  : many-to-many Supplier <-> Barang (1 barang bisa
 *                        banyak supplier)
 *   - supplier_distributor : many-to-many Supplier (induk) <-> Distributor
 *
 * Seeder ini menjamin SETIAP barang aktif memiliki minimal satu supplier,
 * dan setiap distributor terhubung ke minimal satu supplier induk, sehingga
 * alur Buat Pesanan / Penerimaan Barang bisa diuji end-to-end.
 *
 * Idempotent: updateOrInsert pada supplier/distributor (kunci nama + jenis),
 * dan pivot hanya di-insert bila belum ada baris aktif yang sama.
 */
class SupplierDistributorSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $userId = 1;

        $suppliers = [
            ['nama_supplier' => 'PT Kimia Farma', 'alamat' => 'Jakarta', 'telepon' => '021-111111', 'email' => 'kimiafarma@example.com', 'npwp' => '01.000.000.0-000.000'],
            ['nama_supplier' => 'PT Indofarma', 'alamat' => 'Bekasi', 'telepon' => '021-222222', 'email' => 'indofarma@example.com', 'npwp' => '02.000.000.0-000.000'],
            ['nama_supplier' => 'PT Kalbe Farma', 'alamat' => 'Jakarta', 'telepon' => '021-333333', 'email' => 'kalbe@example.com', 'npwp' => '03.000.000.0-000.000'],
            ['nama_supplier' => 'CV Sumber Medika', 'alamat' => 'Surabaya', 'telepon' => '031-444444', 'email' => 'sumbermedika@example.com', 'npwp' => '04.000.000.0-000.000'],
            ['nama_supplier' => 'PT B Braun Indonesia', 'alamat' => 'Jakarta', 'telepon' => '021-555555', 'email' => 'bbraun@example.com', 'npwp' => '05.000.000.0-000.000'],
        ];

        $distributors = [
            ['nama_supplier' => 'PT Distributor Medika Jaya', 'alamat' => 'Jakarta', 'telepon' => '021-666666', 'email' => 'medikajaya@example.com', 'npwp' => '06.000.000.0-000.000'],
            ['nama_supplier' => 'CV Mitra Farma', 'alamat' => 'Bandung', 'telepon' => '022-777777', 'email' => 'mitrafarma@example.com', 'npwp' => '07.000.000.0-000.000'],
            ['nama_supplier' => 'PT Anugrah Pharmindo Lestari', 'alamat' => 'Jakarta', 'telepon' => '021-888888', 'email' => 'apl@example.com', 'npwp' => '08.000.000.0-000.000'],
        ];

        $supplierIds = [];
        foreach ($suppliers as $row) {
            DB::table('supplier')->updateOrInsert(
                ['nama_supplier' => $row['nama_supplier'], 'jenis_supplier' => 'SUPPLIER'],
                array_merge($row, [
                    'jenis_supplier' => 'SUPPLIER',
                    'input_time' => $now,
                    'input_user_id' => $userId,
                    'status_batal' => 0,
                ])
            );

            $id = DB::table('supplier')
                ->where('nama_supplier', $row['nama_supplier'])
                ->where('jenis_supplier', 'SUPPLIER')
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->orderBy('supplier_id')
                ->value('supplier_id');

            $supplierIds[] = $id;
        }

        $distributorIds = [];
        foreach ($distributors as $row) {
            DB::table('supplier')->updateOrInsert(
                ['nama_supplier' => $row['nama_supplier'], 'jenis_supplier' => 'DISTRIBUTOR'],
                array_merge($row, [
                    'jenis_supplier' => 'DISTRIBUTOR',
                    'input_time' => $now,
                    'input_user_id' => $userId,
                    'status_batal' => 0,
                ])
            );

            $id = DB::table('supplier')
                ->where('nama_supplier', $row['nama_supplier'])
                ->where('jenis_supplier', 'DISTRIBUTOR')
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->orderBy('supplier_id')
                ->value('supplier_id');

            $distributorIds[] = $id;
        }

        // Mapping supplier -> barang (semua barang aktif) agar setiap barang
        // memiliki minimal satu supplier.
        $barangIds = DB::table('barang')
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->pluck('barang_id');

        foreach ($supplierIds as $supplierId) {
            foreach ($barangIds as $barangId) {
                $exists = DB::table('barang_supplier')
                    ->where('supplier_id', $supplierId)
                    ->where('barang_id', $barangId)
                    ->where(function ($q) {
                        $q->whereNull('status_batal')->orWhere('status_batal', 0);
                    })
                    ->exists();

                if (! $exists) {
                    DB::table('barang_supplier')->insert([
                        'barang_id' => $barangId,
                        'supplier_id' => $supplierId,
                        'input_time' => $now,
                        'input_user_id' => $userId,
                        'status_batal' => 0,
                    ]);
                }
            }
        }

        // Mapping distributor -> supplier induk (round-robin).
        foreach ($distributorIds as $i => $distributorId) {
            $supplierId = $supplierIds[$i % count($supplierIds)];

            $exists = DB::table('supplier_distributor')
                ->where('distributor_id', $distributorId)
                ->where('supplier_id', $supplierId)
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->exists();

            if (! $exists) {
                DB::table('supplier_distributor')->insert([
                    'supplier_id' => $supplierId,
                    'distributor_id' => $distributorId,
                    'input_time' => $now,
                    'input_user_id' => $userId,
                    'status_batal' => 0,
                ]);
            }
        }
    }
}
