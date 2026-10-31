<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KategoriTindakanSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Baseline kategori tindakan penunjang (pengganti enum lama LAB/RAD/LAIN).
        // Idempotent: updateOrInsert berdasarkan nama_kategori_tindakan (unik).
        $kategoris = [
            'Laboratorium',
            'Radiologi',
            'Lainnya',
            'Administrasi',
            'Kamar',
            'Makanan',
            'Visite',
            'Konsultasi',
            'Emergency',
            'MCU',
        ];

        foreach ($kategoris as $nama) {
            DB::table('kategori_tindakan')->updateOrInsert(
                ['nama_kategori_tindakan' => $nama],
                [
                    'input_time' => $now,
                    'input_user_id' => 1,
                    'status_batal' => 0,
                ]
            );
        }
    }
}
