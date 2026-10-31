<?php

/**
 * Verifikasi order penunjang (form 49, 50, 51).
 *
 * Form ini TIDAK bisa diuji dengan tools/e2e.php karena datanya disimpan di
 * tabel `order_*` (bukan emr_detail) dan membutuhkan item baris yang dipilih
 * lewat master Group Tindakan. Script ini memanggil alur yang sama dengan
 * controller: PenunjangHelper::buatOrder() -> ubahOrder() -> batalkan().
 *
 * Jalankan: docker compose exec -T app php tools/e2e_order.php
 */
use App\Helpers\PenunjangHelper;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require '/var/www/html/vendor/autoload.php';

$app = require '/var/www/html/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// User peminta: ambil user yang punya pegawai.
$user = DB::table('users')->whereNotNull('pegawai_id')->first();
if (! $user) {
    fwrite(STDERR, "Tidak ada user dengan pegawai_id.\n");
    exit(1);
}
Auth::loginUsingId($user->user_id);
session(['profesi_id' => 1]);

$registrasiDetail = DB::table('registrasi_detail')->orderBy('registrasi_detail_id')->first();
if (! $registrasiDetail) {
    fwrite(STDERR, "Tidak ada registrasi_detail.\n");
    exit(1);
}

// Satu tindakan dari group pertama (dibutuhkan buat item order).
$grup = DB::table('group_tindakan_tindakan as gtt')
    ->join('group_tindakan as g', 'g.group_tindakan_id', '=', 'gtt.group_tindakan_id')
    ->where(function ($q) {
        $q->whereNull('gtt.status_batal')->orWhere('gtt.status_batal', 0);
    })
    ->orderBy('gtt.group_tindakan_id')
    ->first(['g.group_tindakan_id', 'g.bagian_id', 'gtt.tindakan_id']);

if (! $grup || ! $grup->bagian_id) {
    fwrite(STDERR, "Tidak ada Group Tindakan dengan bagian tujuan.\n");
    exit(1);
}

$kasir = [
    'lab' => ['Order Laboratorium', 'order_laboratorium', 'order_laboratorium_id', 'order_laboratorium_detail'],
    'rad' => ['Order Radiologi', 'order_radiologi', 'order_radiologi_id', 'order_radiologi_detail'],
    'patologi' => ['Order Patologi Anatomi', 'order_patologi', 'order_patologi_id', 'order_patologi_detail'],
    'mikrobiologi' => ['Order Mikrobiologi', 'order_mikrobiologi', 'order_mikrobiologi_id', 'order_mikrobiologi_detail'],
    'poct' => ['Order POCT', 'order_poct', 'order_poct_id', 'order_poct_detail'],
];

$fail = 0;
$ok = 0;
$detailTujuanIds = [];
$billingIds = [];

foreach ($kasir as $jenis => [$nama, $tabel, $pk, $tabelDetail]) {
    try {
        $orderId = PenunjangHelper::buatOrder($jenis, [
            'registrasi_detail_id' => (int) $registrasiDetail->registrasi_detail_id,
            'bagian_asal_id' => (int) $registrasiDetail->bagian_id,
            'bagian_tujuan_id' => (int) $grup->bagian_id,
            'dokter_id' => (int) $user->pegawai_id,
            'prioritas' => 'BIASA',
            'keterangan' => 'Uji '.$nama,
        ], [
            ['tindakan_id' => (int) $grup->tindakan_id, 'spesimen' => 'Biopsi'],
        ]);

        $header = DB::table($tabel)->where($pk, $orderId)->first();
        $detail = DB::table($tabelDetail)->where($pk, $orderId)->count();
        $detailTujuanIds[] = (int) $header->registrasi_detail_tujuan_id;

        $billingTarget = DB::table('billing_detail as bd')
            ->join('billing as b', 'b.billing_id', '=', 'bd.billing_id')
            ->where('bd.sumber_type', strtoupper($jenis))
            ->where('bd.sumber_id', $orderId)
            ->where(function ($q) {
                $q->whereNull('bd.status_batal')->orWhere('bd.status_batal', 0);
            })
            ->first(['b.billing_id', 'bd.registrasi_detail_id']);

        if ($billingTarget) {
            $billingIds[] = (int) $billingTarget->billing_id;
        }

        // Uji ubahOrder: ganti item.
        PenunjangHelper::ubahOrder($jenis, $orderId, [
            'registrasi_detail_id' => (int) $registrasiDetail->registrasi_detail_id,
            'bagian_asal_id' => (int) $registrasiDetail->bagian_id,
            'bagian_tujuan_id' => (int) $grup->group_tindakan_id,
            'prioritas' => 'CITO',
            'keterangan' => 'Uji diubah '.$nama,
        ], [['tindakan_id' => (int) $grup->tindakan_id, 'spesimen' => 'Sitologi']]);

        $setelahUbah = DB::table($tabel)->where($pk, $orderId)->first();
        $billingSetelahUbah = DB::table('billing_detail as bd')
            ->where('bd.sumber_type', strtoupper($jenis))
            ->where('bd.sumber_id', $orderId)
            ->where('bd.status', '!=', 9)
            ->where(function ($q) {
                $q->whereNull('bd.status_batal')->orWhere('bd.status_batal', 0);
            })
            ->first(['bd.registrasi_detail_id']);

        // Uji batalkan.
        PenunjangHelper::batalkan($jenis, $orderId);
        $setelahBatal = DB::table($tabel)->where($pk, $orderId)->value('status_order');
        $billingSetelahBatal = DB::table('billing_detail')
            ->where('sumber_type', strtoupper($jenis))
            ->where('sumber_id', $orderId)
            ->where('status', '!=', 9)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->count();

        $targetBenar = $billingTarget
            && (int) $billingTarget->registrasi_detail_id === (int) $header->registrasi_detail_tujuan_id;
        $targetUbahBenar = $billingSetelahUbah
            && (int) $billingSetelahUbah->registrasi_detail_id === (int) $header->registrasi_detail_tujuan_id;

        $bagus = $header
            && $header->no_order !== ''
            && $detail >= 1
            && $targetBenar
            && $targetUbahBenar
            && $billingSetelahBatal === 0
            && $setelahUbah->prioritas === 'CITO'
            && (int) $setelahBatal === 3;

        printf("  %-30s order=%d detail=%d ubah=%s batal=%s billing-target=%s %s\n",
            $nama, $orderId, $detail, $setelahUbah->prioritas,
            $setelahBatal, $targetBenar && $targetUbahBenar ? 'OK' : 'SALAH',
            $bagus ? 'OK' : '<<< PERIKSA');

        if ($bagus) {
            $ok++;
        } else {
            $fail++;
        }
    } catch (Throwable $e) {
        printf("  %-30s EXC %s: %s\n", $nama, get_class($e), $e->getMessage());
        $fail++;
    }
}

// Bersihkan data uji (order, billing tujuan, dan registrasi_detail tujuan).
DB::table('order_laboratorium_detail')->delete();
DB::table('order_laboratorium')->delete();
DB::table('order_radiologi_detail')->delete();
DB::table('order_radiologi')->delete();
DB::table('order_patologi_detail')->delete();
DB::table('order_patologi')->delete();
DB::table('order_mikrobiologi_detail')->delete();
DB::table('order_mikrobiologi')->delete();
DB::table('order_poct_detail')->delete();
DB::table('order_poct')->delete();

$detailTujuanIds = array_values(array_unique(array_filter($detailTujuanIds)));
$billingIds = array_values(array_unique(array_filter($billingIds)));

if ($billingIds !== []) {
    DB::table('billing_detail')->whereIn('billing_id', $billingIds)->delete();
    DB::table('billing')->whereIn('billing_id', $billingIds)->delete();
}

// Billing tujuan yang mungkin dibuat setelah ubahOrder (bila id berbeda).
if ($detailTujuanIds !== []) {
    $billingLain = DB::table('billing')->whereIn('registrasi_detail_id', $detailTujuanIds)->pluck('billing_id')->all();
    if ($billingLain !== []) {
        DB::table('billing_detail')->whereIn('billing_id', $billingLain)->delete();
        DB::table('billing')->whereIn('billing_id', $billingLain)->delete();
    }
    DB::table('registrasi_detail')->whereIn('registrasi_detail_id', $detailTujuanIds)->delete();
}

echo "\n".($fail === 0 ? "ALL OK — $ok jenis order\n" : "FAILURES: $fail\n");
exit($fail === 0 ? 0 : 1);
