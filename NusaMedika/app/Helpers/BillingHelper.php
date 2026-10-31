<?php

namespace App\Helpers;

use App\Models\Billing;
use App\Models\BillingDetail;
use App\Models\PasienNasabah;
use App\Models\Registrasi;
use App\Models\RegistrasiDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pusat operasi billing: idempotensi auto-charge, perhitungan total,
 * dan tutup/buka billing. Hanya memakai tabel billing + billing_detail.
 */
class BillingHelper
{
    public static function pastikanBilling(Registrasi $registrasi, ?RegistrasiDetail $detail = null): Billing
    {
        $detail = $detail ?: RegistrasiDetail::where('registrasi_id', $registrasi->registrasi_id)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->orderBy('registrasi_detail_id')
            ->first();

        if (! $detail) {
            throw new RuntimeException('Registrasi belum memiliki detail layanan untuk billing.');
        }

        $billing = Billing::aktif()
            ->where('registrasi_detail_id', $detail->registrasi_detail_id)
            ->first();

        if (! $billing) {
            $pasienNasabah = static::pasienNasabahUtama((int) $registrasi->pasien_id);
            $billing = Billing::create([
                'registrasi_id' => $registrasi->registrasi_id,
                'registrasi_detail_id' => $detail->registrasi_detail_id,
                'pasien_id' => $registrasi->pasien_id,
                'pasien_nasabah_id' => $registrasi->pasien_nasabah_id ?: $pasienNasabah?->pasien_nasabah_id,
                'nasabah_id' => $pasienNasabah?->nasabah_id,
                'jenis_rawat' => $registrasi->jenis_rawat,
                'kelas_ruang_id' => static::kelasRuangId($detail),
                'hak_kelas_ruang_id' => $detail->hak_kelas_id,
                'jenis_billing' => $pasienNasabah ? 'PENJAMIN' : 'FEE_FOR_SERVICE',
                'status_billing' => Billing::OPEN,
                'status_selesai' => 0,
                'tgl_billing' => now(),
                'tgl_masuk' => $registrasi->tgl_masuk,
                'tgl_keluar' => $detail->check_out,
                'input_time' => now(),
                'input_user_id' => Auth::id(),
                'status_batal' => 0,
            ]);

            $billing->update([
                'no_billing' => 'BILL/'.now()->format('Ymd').'/'.str_pad((string) $billing->billing_id, 6, '0', STR_PAD_LEFT),
                'mod_time' => now(),
                'mod_user_id' => Auth::id(),
            ]);
        }

        return $billing->fresh();
    }

    public static function postDetail(Billing $billing, array $data): BillingDetail
    {
        $kodeUnik = trim((string) ($data['kode_unik'] ?? ''));
        if ($kodeUnik === '') {
            throw new RuntimeException('kode_unik billing detail wajib diisi.');
        }

        $existing = BillingDetail::where('kode_unik', $kodeUnik)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        $qty = (float) ($data['qty'] ?? 1);
        $harga = (float) ($data['harga_satuan'] ?? 0);
        $diskonPersen = (float) ($data['diskon_persen'] ?? 0);
        $diskonNominal = (float) ($data['diskon_nominal'] ?? 0);
        $subtotal = max(0, ($qty * $harga) - (($qty * $harga) * $diskonPersen / 100) - $diskonNominal);

        $allowed = [
            'registrasi_detail_id', 'tanggal', 'jenis_layanan',
            'sumber_type', 'sumber_id', 'ref_detail_id', 'kode', 'nama', 'satuan',
            'qty', 'harga_satuan', 'harga_beli', 'diskon_persen', 'diskon_nominal',
            'jasa_sarana', 'jasa_medis', 'bhp', 'is_covered',
            'alasan_tidak_coverage', 'bagian_id',
            'kelas_ruang_id', 'dokter_id', 'profesi_id', 'flag_cito',
            'kode_bpjs', 'kode_inacbg', 'kode_icd9',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));
        $payload = array_merge([
            'registrasi_detail_id' => null,
            'tanggal' => now()->toDateString(),
            'jenis_layanan' => 'MANUAL',
            'kode' => null,
            'nama' => 'Biaya',
            'satuan' => null,
            'harga_beli' => 0,
            'jasa_sarana' => 0,
            'jasa_medis' => 0,
            'bhp' => 0,
            'is_covered' => 1,
            'kena_pajak' => 0,
            'kena_service' => 0,
            'status_batal' => 0,
        ], $payload, [
            'kode_unik' => $kodeUnik,
            'billing_id' => $billing->billing_id,
            'qty' => $qty,
            'harga_satuan' => $harga,
            'diskon_persen' => $diskonPersen,
            'diskon_nominal' => $diskonNominal,
            'subtotal' => $subtotal,
            'pajak' => 0,
            'service' => 0,
            'status' => BillingDetail::POSTED,
            'input_time' => now(),
            'input_user_id' => Auth::id(),
        ]);

        $detail = BillingDetail::create($payload);

        static::hitungTotal($billing);

        return $detail;
    }

    public static function voidDetail(BillingDetail $detail, string $alasan): void
    {
        if ((int) $detail->status === BillingDetail::VOID) {
            return;
        }

        $detail->update([
            'status' => BillingDetail::VOID,
            'status_batal' => 1,
            'void_reason' => $alasan,
            'mod_time' => now(),
            'mod_user_id' => Auth::id(),
        ]);

        static::hitungTotal($detail->billing);
    }

    public static function syncAdministrasi(Registrasi $registrasi, ?RegistrasiDetail $detail = null): ?BillingDetail
    {
        $billing = static::pastikanBilling($registrasi, $detail);
        $detail = $detail ?: RegistrasiDetail::where('registrasi_id', $registrasi->registrasi_id)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->orderBy('registrasi_detail_id')
            ->first();

        $tarif = TarifHelper::administrasi($billing->kelas_ruang_id);

        $kodeUnik = 'ADMIN:'.$registrasi->registrasi_id;
        $pernahAda = BillingDetail::where('kode_unik', $kodeUnik)->exists();
        $existing = BillingDetail::where('kode_unik', $kodeUnik)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->first();

        if ($existing) {
            $bolehIsiHarga = $billing->is_terbuka
                && (int) $existing->status === BillingDetail::POSTED
                && (float) $existing->harga_satuan <= 0
                && $tarif > 0;

            if ($bolehIsiHarga) {
                $qty = (float) $existing->qty;
                $existing->update([
                    'harga_satuan' => $tarif,
                    'subtotal' => max(0, $qty * $tarif),
                    'mod_time' => now(),
                    'mod_user_id' => Auth::id(),
                ]);
                static::hitungTotal($billing);
            }

            return $existing;
        }

        if ($pernahAda) {
            return null;
        }

        return static::postDetail($billing, [
            'kode_unik' => 'ADMIN:'.$registrasi->registrasi_id,
            'registrasi_detail_id' => $detail?->registrasi_detail_id,
            'jenis_layanan' => 'TINDAKAN',
            'sumber_type' => 'REGISTRASI',
            'sumber_id' => $registrasi->registrasi_id,
            'kode' => 'ADMIN',
            'nama' => 'Biaya Administrasi',
            'qty' => 1,
            'harga_satuan' => $tarif,
            'status' => BillingDetail::POSTED,
        ]);
    }

    public static function syncSuket(Registrasi $registrasi, RegistrasiDetail $detail): ?BillingDetail
    {
        if ($detail->harga_suket === null) {
            return null;
        }

        $billing = static::pastikanBilling($registrasi, $detail);

        return static::postDetail($billing, [
            'kode_unik' => 'SUKET:'.$detail->registrasi_detail_id,
            'registrasi_detail_id' => $detail->registrasi_detail_id,
            'jenis_layanan' => 'SUKET',
            'sumber_type' => 'REGISTRASI_DETAIL',
            'sumber_id' => $detail->registrasi_detail_id,
            'kode' => 'SUKET',
            'nama' => 'Surat Keterangan / MCU',
            'qty' => 1,
            'harga_satuan' => (float) $detail->harga_suket,
            'status' => BillingDetail::POSTED,
        ]);
    }

    public static function syncOrder(string $jenis, int $orderId): int
    {
        $jenis = strtolower($jenis);
        $map = [
            'lab' => ['order_laboratorium', 'order_laboratorium_id', 'order_laboratorium_detail', 'order_laboratorium_id', 'order_laboratorium_detail_id', 'ORDER_LAB'],
            'rad' => ['order_radiologi', 'order_radiologi_id', 'order_radiologi_detail', 'order_radiologi_id', 'order_radiologi_detail_id', 'ORDER_RAD'],
            'patologi' => ['order_patologi', 'order_patologi_id', 'order_patologi_detail', 'order_patologi_id', 'order_patologi_detail_id', 'ORDER_PATOLOGI'],
            'mikrobiologi' => ['order_mikrobiologi', 'order_mikrobiologi_id', 'order_mikrobiologi_detail', 'order_mikrobiologi_id', 'order_mikrobiologi_detail_id', 'ORDER_MIKROBIOLOGI'],
            'poct' => ['order_poct', 'order_poct_id', 'order_poct_detail', 'order_poct_id', 'order_poct_detail_id', 'ORDER_POCT'],
        ];

        if (! isset($map[$jenis])) {
            throw new RuntimeException('Jenis order tidak dikenal: '.$jenis);
        }

        [$table, $pk, $detailTable, $detailFk, $detailPk, $jenisLayanan] = $map[$jenis];

        $order = DB::table($table)->where($pk, $orderId)->first();
        if (! $order) {
            throw new RuntimeException('Order tidak ditemukan.');
        }

        $registrasi = Registrasi::findOrFail($order->registrasi_id);

        // Biaya order masuk ke registrasi_detail TUJUAN (bagian lab/rad/patologi/
        // mikrobiologi/POCT), bukan detail asal. Dengan begitu rincian billing
        // dapat dikelompokkan per unit layanan yang benar-benar mengerjakan order.
        $detail = RegistrasiDetail::find($order->registrasi_detail_tujuan_id)
            ?: RegistrasiDetail::find($order->registrasi_detail_id);
        $billing = static::pastikanBilling($registrasi, $detail);
        $count = 0;

        $items = DB::table($detailTable)->where($detailFk, $orderId)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })->get();

        foreach ($items as $item) {
            static::postDetail($billing, [
                'kode_unik' => 'ORDER:'.$jenis.':'.$item->{$detailPk},
                'registrasi_detail_id' => $detail?->registrasi_detail_id ?? $order->registrasi_detail_id,
                'jenis_layanan' => $jenisLayanan,
                'sumber_type' => strtoupper($jenis),
                'sumber_id' => $orderId,
                'ref_detail_id' => $item->{$detailPk},
                'kode' => $item->tindakan_id ? (string) $item->tindakan_id : null,
                'nama' => $item->nama_tindakan,
                'qty' => 1,
                'harga_satuan' => (float) $item->harga,
                'bagian_id' => $detail?->bagian_id,
                'kelas_ruang_id' => $billing->kelas_ruang_id,
                'status' => BillingDetail::POSTED,
            ]);
            $count++;
        }

        return $count;
    }

    public static function voidEmr(int $emrId): void
    {
        $details = BillingDetail::where('sumber_type', 'EMR')
            ->where('sumber_id', $emrId)
            ->where('status', '!=', BillingDetail::VOID)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })->get();

        foreach ($details as $detail) {
            static::voidDetail($detail, 'Tindakan medis diubah/dibatalkan.');
        }
    }

    public static function voidOrder(string $jenis, int $orderId): void
    {
        $jenis = strtoupper($jenis);
        $details = BillingDetail::where('sumber_type', $jenis)
            ->where('sumber_id', $orderId)
            ->where('status', '!=', BillingDetail::VOID)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })->get();

        foreach ($details as $detail) {
            static::voidDetail($detail, 'Order penunjang diubah/dibatalkan.');
        }
    }

    public static function syncResepDispense(int $peresepanObatDetailId): int
    {
        $resepDetail = DB::table('peresepan_obat_detail')->where('peresepan_obat_detail_id', $peresepanObatDetailId)->first();
        if (! $resepDetail) {
            return 0;
        }

        $header = DB::table('peresepan_obat')->where('peresepan_obat_id', $resepDetail->peresepan_obat_id)->first();
        if (! $header) {
            return 0;
        }

        $registrasi = Registrasi::find($header->registrasi_id);
        if (! $registrasi) {
            return 0;
        }

        $detail = RegistrasiDetail::find($header->registrasi_detail_id);
        $billing = static::pastikanBilling($registrasi, $detail);

        $rows = DB::table('kartu_stock')
            ->where('ref_peresepan_obat_detail_id', $peresepanObatDetailId)
            ->where('jenis_mutasi', 5)
            ->where('qty_keluar', '>', 0)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->get();

        $count = 0;
        foreach ($rows as $row) {
            $barang = DB::table('barang')->where('barang_id', $row->barang_id)->first();
            $harga = (float) ($row->harga_jual ?? 0);
            if ($harga <= 0) {
                $harga = TarifHelper::barang((int) $row->barang_id, $row->no_batch, $row->bagian_id)['harga_jual'];
            }

            $qty = (float) $row->qty_keluar;
            static::postDetail($billing, [
                'kode_unik' => 'RESEP:'.$row->kartu_stock_id,
                'registrasi_detail_id' => $header->registrasi_detail_id,
                'jenis_layanan' => 'RESEP',
                'sumber_type' => 'KARTU_STOCK',
                'sumber_id' => $row->kartu_stock_id,
                'ref_detail_id' => $peresepanObatDetailId,
                'kode' => $barang->kode_barang ?? null,
                'nama' => $barang->nama_barang ?? 'Obat',
                'satuan' => null,
                'qty' => $qty,
                'harga_satuan' => $harga,
                'harga_beli' => (float) ($row->harga_beli ?? 0),
                'bagian_id' => $row->bagian_id,
                'kelas_ruang_id' => $billing->kelas_ruang_id,
                'dokter_id' => $header->dokter_id ?? null,
                'status' => BillingDetail::POSTED,
            ]);
            $count++;
        }

        return $count;
    }

    public static function syncTindakanMedis(int $emrId): int
    {
        $emr = DB::table('emr')->where('emr_id', $emrId)->first();
        if (! $emr) {
            return 0;
        }

        $jenis = DB::table('emr_detail')->where('emr_id', $emrId)->where('variabel', 'tindakan_id')
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })->value('value');

        $registrasi = Registrasi::find($emr->registrasi_id);
        if (! $registrasi) {
            return 0;
        }

        $detail = RegistrasiDetail::find($emr->registrasi_detail_id);
        $billing = static::pastikanBilling($registrasi, $detail);
        $count = 0;

        if ($jenis) {
            $tindakan = DB::table('tindakan')->where('tindakan_id', $jenis)->first();
            $tarif = TarifHelper::tindakan((int) $jenis, $billing->kelas_ruang_id);
            static::postDetail($billing, [
                'kode_unik' => 'TINDAKAN_MEDIS:'.$emrId.':TINDAKAN',
                'registrasi_detail_id' => $emr->registrasi_detail_id,
                'jenis_layanan' => 'TINDAKAN_MEDIS',
                'sumber_type' => 'EMR',
                'sumber_id' => $emrId,
                'kode' => $tindakan->kode_tindakan ?? null,
                'nama' => $tindakan->nama_tindakan ?? 'Tindakan Medis',
                'qty' => 1,
                'harga_satuan' => $tarif['tarif'],
                'jasa_sarana' => $tarif['jasa_sarana'],
                'jasa_medis' => $tarif['jasa_medis'],
                'bhp' => $tarif['bhp'],
                'dokter_id' => DB::table('emr_detail')->where('emr_id', $emrId)->where('variabel', 'dokter_persetujuan_id')->value('value'),
                'status' => BillingDetail::POSTED,
            ]);
            $count++;
        }

        $bmhp = DB::table('kartu_stock')
            ->where('ref_emr_id', $emrId)
            ->where('jenis_mutasi', 4)
            ->where('qty_keluar', '>', 0)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })->get();

        foreach ($bmhp as $row) {
            $barang = DB::table('barang')->where('barang_id', $row->barang_id)->first();
            $harga = (float) ($row->harga_jual ?? 0);
            if ($harga <= 0) {
                $harga = TarifHelper::barang((int) $row->barang_id, $row->no_batch, $row->bagian_id)['harga_jual'];
            }

            static::postDetail($billing, [
                'kode_unik' => 'TINDAKAN_MEDIS:'.$emrId.':BMHP:'.$row->kartu_stock_id,
                'registrasi_detail_id' => $emr->registrasi_detail_id,
                'jenis_layanan' => 'TINDAKAN_MEDIS',
                'sumber_type' => 'KARTU_STOCK',
                'sumber_id' => $row->kartu_stock_id,
                'ref_detail_id' => $emrId,
                'kode' => $barang->kode_barang ?? null,
                'nama' => $barang->nama_barang ?? 'BMHP',
                'qty' => (float) $row->qty_keluar,
                'harga_satuan' => $harga,
                'harga_beli' => (float) ($row->harga_beli ?? 0),
                'bagian_id' => $row->bagian_id,
                'kelas_ruang_id' => $billing->kelas_ruang_id,
                'status' => BillingDetail::POSTED,
            ]);
            $count++;
        }

        return $count;
    }

    public static function syncKamar(Registrasi $registrasi): int
    {
        $details = RegistrasiDetail::where('registrasi_id', $registrasi->registrasi_id)
            ->whereNotNull('check_in')
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })->get();

        $count = 0;
        foreach ($details as $detail) {
            $billing = static::pastikanBilling($registrasi, $detail);
            $mulai = Carbon::parse($detail->check_in)->startOfDay();
            $selesai = $detail->check_out ? Carbon::parse($detail->check_out)->startOfDay() : now()->startOfDay();
            $kelasId = static::kelasRuangId($detail);

            for ($tanggal = $mulai->copy(); $tanggal->lte($selesai); $tanggal->addDay()) {
                $tarif = TarifHelper::kamar($kelasId);
                static::postDetail($billing, [
                    'kode_unik' => 'KAMAR:'.$detail->registrasi_detail_id.':'.$tanggal->toDateString(),
                    'registrasi_detail_id' => $detail->registrasi_detail_id,
                    'tanggal' => $tanggal->toDateString(),
                    'jenis_layanan' => 'KAMAR',
                    'sumber_type' => 'REGISTRASI_DETAIL',
                    'sumber_id' => $detail->registrasi_detail_id,
                    'kode' => 'KAMAR',
                    'nama' => 'Kamar Rawat Inap',
                    'qty' => 1,
                    'harga_satuan' => $tarif,
                    'bagian_id' => $detail->bagian_id,
                    'kelas_ruang_id' => $kelasId,
                    'status' => BillingDetail::POSTED,
                ]);
                $count++;
            }
        }

        return $count;
    }

    public static function syncKonsultasi(Registrasi $registrasi, RegistrasiDetail $detail, ?int $dokterId = null): ?BillingDetail
    {
        $billing = static::pastikanBilling($registrasi, $detail);

        return static::postDetail($billing, [
            'kode_unik' => 'KONSULTASI:'.$detail->registrasi_detail_id,
            'registrasi_detail_id' => $detail->registrasi_detail_id,
            'jenis_layanan' => 'KONSULTASI',
            'sumber_type' => 'REGISTRASI_DETAIL',
            'sumber_id' => $detail->registrasi_detail_id,
            'kode' => 'KONSULTASI',
            'nama' => 'Jasa Konsultasi',
            'qty' => 1,
            'harga_satuan' => TarifHelper::komponen('KONSULTASI'),
            'dokter_id' => $dokterId,
            'status' => BillingDetail::POSTED,
        ]);
    }

    public static function hitungTotal(Billing $billing): Billing
    {
        $details = BillingDetail::where('billing_id', $billing->billing_id)
            ->where('status', BillingDetail::POSTED)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })->get();

        $subtotal = round((float) $details->sum('subtotal'), 2);
        $diskon = round(max(0, (float) $billing->diskon), 2);
        $total = round(max(0, $subtotal - $diskon), 2);

        $status = (int) $billing->status_billing;
        if (! in_array($status, [Billing::CLOSED, Billing::VOID], true)) {
            $status = Billing::OPEN;
        }

        $billing->update([
            'subtotal' => $subtotal,
            'diskon' => $diskon,
            'pajak' => 0,
            'service' => 0,
            'materai' => 0,
            'pembulatan' => 0,
            'total' => $total,
            'jaminan_penjamin' => 0,
            'tanggungan_pasien' => $total,
            'dibayar' => 0,
            'sisa' => $total,
            'deposit_terpakai' => 0,
            'write_off' => 0,
            'status_billing' => $status,
            'mod_time' => now(),
            'mod_user_id' => Auth::id(),
        ]);

        return $billing->fresh();
    }

    public static function tutup(Billing $billing, ?string $catatan = null): Billing
    {
        if ((int) $billing->status_billing === Billing::VOID) {
            throw new RuntimeException('Billing yang dibatalkan tidak dapat ditutup.');
        }

        if ((int) $billing->status_billing === Billing::CLOSED && (int) $billing->status_selesai === 1) {
            return $billing;
        }

        $pending = BillingDetail::where('billing_id', $billing->billing_id)
            ->where('status', BillingDetail::PENDING)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })->exists();

        if ($pending) {
            throw new RuntimeException('Masih ada rincian billing yang tertunda.');
        }

        $billing = static::hitungTotal($billing);

        $billing->update([
            'status_billing' => Billing::CLOSED,
            'status_selesai' => 1,
            'no_closing' => null,
            'closed_at' => now(),
            'closed_by' => Auth::id(),
            'catatan' => $catatan ?: $billing->catatan,
            'mod_time' => now(),
            'mod_user_id' => Auth::id(),
        ]);

        return $billing->fresh();
    }

    public static function buka(Billing $billing, string $alasan = 'Dibuka kembali dari halaman billing'): Billing
    {
        if ((int) $billing->status_billing !== Billing::CLOSED) {
            throw new RuntimeException('Hanya billing yang sudah ditutup yang dapat dibuka.');
        }

        $billing->update([
            'status_billing' => Billing::OPEN,
            'status_selesai' => 0,
            'closed_at' => null,
            'closed_by' => null,
            'no_closing' => null,
            'mod_time' => now(),
            'mod_user_id' => Auth::id(),
        ]);

        return $billing->fresh();
    }

    public static function void(Billing $billing, string $alasan): Billing
    {
        if ((int) $billing->status_billing === Billing::VOID) {
            return $billing;
        }

        $billing->update([
            'status_billing' => Billing::VOID,
            'status_batal' => 1,
            'void_at' => now(),
            'void_by' => Auth::id(),
            'void_reason' => $alasan,
            'mod_time' => now(),
            'mod_user_id' => Auth::id(),
        ]);

        BillingDetail::where('billing_id', $billing->billing_id)->update(['status' => BillingDetail::VOID, 'status_batal' => 1]);

        return $billing->fresh();
    }

    public static function voidRegistrasi(int $registrasiId, string $alasan = 'Registrasi dibatalkan'): void
    {
        $billings = Billing::aktif()->where('registrasi_id', $registrasiId)->get();

        foreach ($billings as $billing) {
            static::void($billing, $alasan);
        }
    }

    public static function kelasRuangId(RegistrasiDetail $detail): ?int
    {
        return $detail->hak_kelas_id
            ?: $detail->kelas_id;
    }

    public static function pasienNasabahUtama(int $pasienId): ?PasienNasabah
    {
        return PasienNasabah::where('pasien_id', $pasienId)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->orderByDesc('is_primary')
            ->orderByDesc('pasien_nasabah_id')
            ->first();
    }
}
