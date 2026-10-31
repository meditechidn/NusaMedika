<?php

/**
 * Harness billing end-to-end: registrasi → auto-charge administrasi →
 * posting manual → diskon → tutup → buka → void → cetak.
 * Billing hanya memakai tabel billing + billing_detail.
 *
 * Pakai:
 *   docker compose exec -T app php tools/e2e_billing.php
 */

use App\Helpers\BillingHelper;
use App\Helpers\TarifHelper;
use App\Models\Billing;
use App\Models\BillingDetail;
use App\Models\Registrasi;
use App\Models\RegistrasiDetail;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['app.debug' => true]);

const MARKER = 'E2E-BILLING';

$fail = 0;

function cek(string $label, bool $ok, string $info = ''): void
{
    global $fail;
    if (! $ok) {
        $fail++;
    }
    printf("  %-68s %s %s\n", $label, $ok ? 'OK' : 'GAGAL', $info);
}

function sesiBaru(int $userId)
{
    $s = app('session')->driver();
    $s->start();
    $s->invalidate();
    $s->start();
    Auth::loginUsingId($userId);
    $profesi = (int) DB::table('pegawai')
        ->where('pegawai_id', DB::table('users')->where('user_id', $userId)->value('pegawai_id'))
        ->value('profesi_id');
    session(['profesi_id' => $profesi > 0 ? $profesi : 1]);

    return $s;
}

function tembak(HttpKernel $K, $s, int $userId, string $method, string $uri, array $data = [])
{
    if (strtoupper($method) !== 'GET' && ! isset($data['_token'])) {
        $data['_token'] = $s->token();
    }
    $r = Request::create($uri, strtoupper($method), $data);
    $r->headers->set('referer', 'http://localhost/');
    $r->setLaravelSession($s);
    $r->setUserResolver(fn () => DB::table('users')->where('user_id', $userId)->first());
    app()->instance('request', $r);

    return $K->handle($r);
}

function bersihkan(?int $regId = null, ?int $pasienId = null): void
{
    if ($regId) {
        $billingIds = DB::table('billing')->where('registrasi_id', $regId)->pluck('billing_id');
        if ($billingIds->isNotEmpty()) {
            DB::table('billing_detail')->whereIn('billing_id', $billingIds)->delete();
            DB::table('billing')->whereIn('billing_id', $billingIds)->delete();
        }
        DB::table('registrasi_detail')->where('registrasi_id', $regId)->delete();
        DB::table('registrasi')->where('registrasi_id', $regId)->delete();
    }

    if ($pasienId) {
        DB::table('pasien')->where('pasien_id', $pasienId)->delete();
    }
}

$K = $app->make(HttpKernel::class);
$s1 = sesiBaru(1);

$lama = DB::table('registrasi')->where('memo', MARKER)->get(['registrasi_id', 'pasien_id']);
foreach ($lama as $r) {
    bersihkan((int) $r->registrasi_id, (int) $r->pasien_id);
}

$pasienId = (int) DB::table('pasien')->insertGetId([
    'nama_pasien' => 'PASIEN UJI BILLING',
    'no_mr' => 'E2E'.substr((string) time(), -7),
    'jenis_kelamin' => 'L',
    'ktp' => MARKER,
    'input_time' => now(),
    'input_user_id' => 1,
    'status_batal' => 0,
], 'pasien_id');
$bagianIds = DB::table('bagian')
    ->where(function ($q) {
        $q->whereNull('status_batal')->orWhere('status_batal', 0);
    })
    ->orderBy('bagian_id')
    ->limit(2)
    ->pluck('bagian_id')
    ->map(fn ($id) => (int) $id)
    ->all();
$bagianId = $bagianIds[0];
$bagian2Id = $bagianIds[1] ?? $bagianId;
$namaBagian = (string) DB::table('bagian')->where('bagian_id', $bagianId)->value('nama_bagian');
$namaBagian2 = (string) DB::table('bagian')->where('bagian_id', $bagian2Id)->value('nama_bagian');
$regId = (int) DB::table('registrasi')->insertGetId([
    'pasien_id' => $pasienId,
    'jenis_rawat' => env('JENIS_RAWAT_RJ', 'RJ'),
    'tgl_masuk' => now(),
    'memo' => MARKER,
    'status_batal' => 0,
    'input_time' => now(),
    'input_user_id' => 1,
], 'registrasi_id');
$rdId = (int) DB::table('registrasi_detail')->insertGetId([
    'registrasi_id' => $regId,
    'tgl_daftar' => now(),
    'bagian_id' => $bagianId,
    'terima_dari' => 'DALAM',
    'status_batal' => 0,
    'input_time' => now(),
    'input_user_id' => 1,
], 'registrasi_detail_id');
$rd2Id = (int) DB::table('registrasi_detail')->insertGetId([
    'registrasi_id' => $regId,
    'tgl_daftar' => now(),
    'bagian_id' => $bagianId,
    'terima_dari' => 'DALAM',
    'status_batal' => 0,
    'input_time' => now(),
    'input_user_id' => 1,
], 'registrasi_detail_id');

// Registrasi anak (mis. IGD → rawat inap) untuk menguji episode gabungan.
$reg2Id = (int) DB::table('registrasi')->insertGetId([
    'pasien_id' => $pasienId,
    'jenis_rawat' => env('JENIS_RAWAT_RI', 'RI'),
    'referensi_registrasi_id' => $regId,
    'tgl_masuk' => now()->addMinutes(5),
    'memo' => MARKER,
    'status_batal' => 0,
    'input_time' => now(),
    'input_user_id' => 1,
], 'registrasi_id');
$rd3Id = (int) DB::table('registrasi_detail')->insertGetId([
    'registrasi_id' => $reg2Id,
    'tgl_daftar' => now()->addMinutes(5),
    'bagian_id' => $bagian2Id,
    'terima_dari' => 'INTERNAL',
    'status_batal' => 0,
    'input_time' => now(),
    'input_user_id' => 1,
], 'registrasi_detail_id');

$reg = Registrasi::findOrFail($regId);
$reg2 = Registrasi::findOrFail($reg2Id);
$rd = RegistrasiDetail::findOrFail($rdId);
$rd2 = RegistrasiDetail::findOrFail($rd2Id);
$rd3 = RegistrasiDetail::findOrFail($rd3Id);

echo "== FASE 1: AUTO-CHARGE ADMINISTRASI ==\n";

$admin = BillingHelper::syncAdministrasi($reg, $rd);
$billing = Billing::aktif()->where('registrasi_detail_id', $rdId)->first();

cek('billing terbit per registrasi_detail', $billing !== null && (int) $billing->registrasi_detail_id === $rdId, 'billing='.($billing->billing_id ?? '-'));
cek('nomor billing otomatis terisi', $billing && str_starts_with((string) $billing->no_billing, 'BILL/'), (string) ($billing->no_billing ?? '-'));
cek('detail administrasi tercatat', $admin !== null && $admin->kode === 'ADMIN', 'detail='.($admin->billing_detail_id ?? '-'));
cek('billing tanpa kolom pembayaran', $billing && (float) $billing->dibayar === 0.0 && (float) $billing->sisa === (float) $billing->total);

$billing2 = BillingHelper::pastikanBilling($reg, $rd2);
cek('detail layanan kedua punya billing sendiri', (int) $billing2->billing_id !== (int) $billing->billing_id, 'billing2='.$billing2->billing_id);
cek('total billing hanya memuat detail sendiri', Billing::aktif()->where('registrasi_detail_id', $rdId)->count() === 1);

$billing3 = BillingHelper::pastikanBilling($reg2, $rd3);
BillingHelper::postDetail($billing3, [
    'kode_unik' => 'E2E:RI:1',
    'registrasi_detail_id' => $rd3Id,
    'jenis_layanan' => 'TINDAKAN',
    'nama' => 'Tindakan Episode Anak',
    'qty' => 1,
    'harga_satuan' => 15000,
]);
cek('registrasi anak punya billing pada detailnya', (int) $billing3->registrasi_detail_id === $rd3Id, 'billing3='.$billing3->billing_id);

echo "== FASE 2: POSTING MANUAL & DISKON ==\n";

$item = BillingHelper::postDetail($billing, [
    'kode_unik' => 'E2E:TINDAKAN:1',
    'registrasi_detail_id' => $rdId,
    'jenis_layanan' => 'TINDAKAN',
    'nama' => 'Tindakan Uji',
    'qty' => 2,
    'harga_satuan' => 5000,
    'diskon_nominal' => 500,
]);
$billing = BillingHelper::hitungTotal($billing->fresh());
cek('subtotal item dihitung (qty x harga - diskon)', (float) $item->subtotal === 9500.0, 'subtotal='.$item->subtotal);
cek('total billing = subtotal - diskon billing', (float) $billing->total === (float) $billing->subtotal - (float) $billing->diskon, 'total='.$billing->total);

$ulang = BillingHelper::postDetail($billing, [
    'kode_unik' => 'E2E:TINDAKAN:1',
    'registrasi_detail_id' => $rdId,
    'jenis_layanan' => 'TINDAKAN',
    'nama' => 'Tindakan Uji',
    'qty' => 2,
    'harga_satuan' => 5000,
]);
cek('kode_unik mencegah duplikasi auto-charge', (int) $ulang->billing_detail_id === (int) $item->billing_detail_id);

$billing->update(['diskon' => 1000, 'mod_time' => now(), 'mod_user_id' => 1]);
$billing = BillingHelper::hitungTotal($billing);
cek('diskon billing mengurangi total', (float) $billing->total === (float) $billing->subtotal - 1000.0, 'total='.$billing->total);
cek('pajak/service/materai/pembulatan selalu nol', (float) $billing->pajak === 0.0 && (float) $billing->service === 0.0 && (float) $billing->materai === 0.0 && (float) $billing->pembulatan === 0.0);

echo "== FASE 3: TUTUP & BUKA BILLING ==\n";

$billing = BillingHelper::tutup($billing);
cek('tutup billing → CLOSED tanpa no_closing', (int) $billing->status_billing === Billing::CLOSED && (int) $billing->status_selesai === 1 && ! $billing->no_closing, 'status='.$billing->status_billing);
cek('billing kedua tetap terbuka', (int) $billing2->fresh()->status_billing === Billing::OPEN);

$billing = BillingHelper::buka($billing);
cek('buka billing → OPEN', (int) $billing->status_billing === Billing::OPEN && (int) $billing->status_selesai === 0 && ! $billing->no_closing);

$billing = BillingHelper::tutup($billing);
cek('tutup ulang tetap tanpa no_closing', (int) $billing->status_billing === Billing::CLOSED && ! $billing->no_closing);
$billing = BillingHelper::buka($billing);

echo "== FASE 4: VOID DETAIL & VOID BILLING ==\n";

$sebelum = (float) $billing->subtotal;
BillingHelper::voidDetail($item, 'Uji void item');
$billing = $billing->fresh();
cek('void detail mengurangi subtotal', (float) $billing->subtotal === round($sebelum - (float) $item->subtotal, 2), 'subtotal='.$billing->subtotal);
cek('void detail menyisakan administrasi', BillingDetail::where('billing_id', $billing->billing_id)->posted()->count() === 1);

BillingHelper::void($billing, 'Uji void billing');
$billing = $billing->fresh();
cek('void billing → VOID + status_batal', (int) $billing->status_billing === Billing::VOID && (int) $billing->status_batal === 1);

$billingBaru = BillingHelper::pastikanBilling($reg, $rd);
cek('billing baru terbit setelah billing lama di-void', (int) $billingBaru->billing_id !== (int) $billing->billing_id && $billingBaru->is_terbuka);

echo "== FASE 5: HTTP HALAMAN BILLING ==\n";

$resp = tembak($K, $s1, 1, 'GET', '/daftar_billing');
cek('GET daftar_billing', $resp->getStatusCode() === 200, 'status='.$resp->getStatusCode());

$resp = tembak($K, $s1, 1, 'GET', '/daftar_billing?q='.$regId);
$htmlList = (string) $resp->getContent();
cek('GET daftar_billing dengan pencarian ID numerik', $resp->getStatusCode() === 200, 'status='.$resp->getStatusCode());
cek('daftar billing menggabungkan episode RJ + RI', str_contains($htmlList, 'EP-'.$regId) && str_contains($htmlList, 'RJ') && str_contains($htmlList, 'RI'), 'root='.$regId);

$resp = tembak($K, $s1, 1, 'GET', '/billing_pasien?billing='.$billingBaru->billing_id);
$htmlDetail = (string) $resp->getContent();
cek('GET billing_pasien', $resp->getStatusCode() === 200, 'status='.$resp->getStatusCode());
cek('detail billing menampilkan section per registrasi_detail',
    str_contains($htmlDetail, 'EP-'.$regId)
        && str_contains($htmlDetail, $namaBagian)
        && str_contains($htmlDetail, $namaBagian2),
    'bagian='.$namaBagian.'|'.$namaBagian2);

$resp = tembak($K, $s1, 1, 'GET', '/billing_pasien?billing='.$billing3->billing_id);
$htmlAnak = (string) $resp->getContent();
cek('billing anak membuka episode root yang sama', $resp->getStatusCode() === 200 && str_contains($htmlAnak, 'EP-'.$regId), 'status='.$resp->getStatusCode());

$resp = tembak($K, $s1, 1, 'GET', '/billing_pasien/'.$billingBaru->billing_id.'/cetak');
cek('GET cetak billing', $resp->getStatusCode() === 200, 'status='.$resp->getStatusCode());

$resp = tembak($K, $s1, 1, 'GET', '/billing_pasien/cetak-episode?registrasi_id='.$regId);
$htmlEpisode = (string) $resp->getContent();
cek('GET cetak episode', $resp->getStatusCode() === 200 && str_contains($htmlEpisode, 'Billing Episode EP-'.$regId) && str_contains($htmlEpisode, 'REG-'.$reg2Id), 'status='.$resp->getStatusCode());

$resp = tembak($K, $s1, 1, 'GET', '/billing_pasien/cetak-pasien?pasien_id='.$pasienId);
$htmlPasien = (string) $resp->getContent();
cek('GET cetak seluruh billing pasien', $resp->getStatusCode() === 200 && str_contains($htmlPasien, 'Seluruh Billing Pasien') && str_contains($htmlPasien, 'REG-'.$regId), 'status='.$resp->getStatusCode());

$hargaKelas = DB::table('tarif')
    ->whereNotNull('kelas_ruang_id')
    ->where(function ($q) {
        $q->whereNull('status_batal')->orWhere('status_batal', 0);
    })
    ->orderBy('tarif_id')
    ->first();
if ($hargaKelas) {
    $kelasUji = (int) $hargaKelas->kelas_ruang_id;
    $tindakanUji = (int) $hargaKelas->tindakan_id;
    $billingBaru->update(['kelas_ruang_id' => $kelasUji]);
    $perkiraan = TarifHelper::tindakan($tindakanUji, $kelasUji);
    $resp = tembak($K, $s1, 1, 'GET', '/billing_pasien/harga?billing_id='.$billingBaru->billing_id.'&jenis=TINDAKAN&item_id='.$tindakanUji);
    $jsonHarga = json_decode((string) $resp->getContent(), true);
    cek('harga tindakan mengikuti kelas billing', $resp->getStatusCode() === 200 && abs((float) ($jsonHarga['harga'] ?? -1) - (float) $perkiraan['tarif']) < 0.01, 'harga='.($jsonHarga['harga'] ?? '-'));
}

$resp = tembak($K, $s1, 1, 'POST', '/billing_pasien/tutup-semua', ['registrasi_id' => $regId]);
$masihTerbuka = Billing::aktif()
    ->whereIn('registrasi_id', [$regId, $reg2Id])
    ->where('status_billing', Billing::OPEN)
    ->exists();
cek('POST tutup-semua menutup seluruh billing episode', $resp->getStatusCode() === 302 && ! $masihTerbuka, 'status='.$resp->getStatusCode());

$resp = tembak($K, $s1, 1, 'POST', '/billing_pasien/buka-semua', ['registrasi_id' => $regId]);
$masihTertutup = Billing::aktif()
    ->whereIn('registrasi_id', [$regId, $reg2Id])
    ->where('status_billing', Billing::CLOSED)
    ->exists();
cek('POST buka-semua membuka seluruh billing episode', $resp->getStatusCode() === 302 && ! $masihTertutup, 'status='.$resp->getStatusCode());

$resp = tembak($K, $s1, 1, 'POST', '/billing_pasien/buka-semua', ['registrasi_id' => $regId]);
cek('buka-semua tanpa billing tertutup memberi pesan error', $resp->getStatusCode() === 302, 'status='.$resp->getStatusCode());

$resp = tembak($K, $s1, 1, 'POST', '/billing_pasien/tutup-semua', ['registrasi_id' => $regId]);
cek('POST tutup-semua ulang setelah buka-semua', $resp->getStatusCode() === 302, 'status='.$resp->getStatusCode());

$resp = tembak($K, $s1, 1, 'POST', '/billing_pasien/tutup-semua', ['registrasi_id' => $regId]);
cek('tutup-semua tanpa billing terbuka memberi pesan error', $resp->getStatusCode() === 302, 'status='.$resp->getStatusCode());

echo "== FASE 6: BERSIHKAN DATA UJI ==\n";
bersihkan($reg2Id);
bersihkan($regId, $pasienId);
cek('data uji terhapus', DB::table('registrasi')->whereIn('registrasi_id', [$regId, $reg2Id])->doesntExist() && DB::table('billing')->whereIn('registrasi_id', [$regId, $reg2Id])->doesntExist());

echo "\n".($fail === 0 ? "SEMUA OK\n" : "GAGAL: {$fail} cek\n");
exit($fail === 0 ? 0 : 1);
