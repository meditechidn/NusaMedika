<?php

namespace App\Http\Controllers\Billing\Transaksi\DaftarBilling;

use App\Helpers\BillingAkses;
use App\Helpers\RegistrasiEpisodeHelper;
use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\Registrasi;
use App\Models\RegistrasiDetail;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class DaftarBillingController extends Controller
{
    public function index(Request $request)
    {
        BillingAkses::authorizeAny([BillingAkses::DAFTAR_BILLING, BillingAkses::BILLING_PASIEN]);

        $cari = trim((string) $request->get('q'));
        $status = $request->get('status');
        $jenisRawat = $request->get('jenis_rawat');
        $dari = $request->get('dari');
        $sampai = $request->get('sampai');

        // Tahap 1: cari billing yang cocok dengan filter pencarian. Hasilnya
        // dipakai untuk menentukan episode (root registrasi) yang ditampilkan.
        $kandidat = Billing::query()
            ->aktif()
            ->when($cari !== '', function ($q) use ($cari) {
                $q->where(function ($sub) use ($cari) {
                    $sub->where('no_billing', 'ilike', '%'.$cari.'%')
                        ->orWhereRaw('CAST(registrasi_id AS TEXT) LIKE ?', ['%'.$cari.'%'])
                        ->orWhereHas('pasien', function ($pasien) use ($cari) {
                            $pasien->where('nama_pasien', 'ilike', '%'.$cari.'%')
                                ->orWhere('no_mr', 'ilike', '%'.$cari.'%');
                        });
                });
            })
            ->when($jenisRawat !== null && $jenisRawat !== '', fn ($q) => $q->where('jenis_rawat', $jenisRawat))
            ->when($dari, fn ($q) => $q->whereDate('tgl_billing', '>=', $dari))
            ->when($sampai, fn ($q) => $q->whereDate('tgl_billing', '<=', $sampai))
            ->orderByDesc('billing_id')
            ->get(['billing_id', 'registrasi_id']);

        $rootPerRegistrasi = [];
        $rootIds = $kandidat
            ->pluck('registrasi_id')
            ->filter()
            ->unique()
            ->map(function ($registrasiId) use (&$rootPerRegistrasi) {
                $registrasiId = (int) $registrasiId;

                return $rootPerRegistrasi[$registrasiId] ??= RegistrasiEpisodeHelper::rootId($registrasiId);
            })
            ->unique()
            ->values()
            ->all();

        $episodes = collect();

        if ($rootIds !== []) {
            $memberMap = RegistrasiEpisodeHelper::membersForRoots($rootIds);
            $semuaAnggota = collect($memberMap)->flatten()->unique()->values()->all();

            $rootUntukRegistrasi = [];
            foreach ($memberMap as $rootId => $anggota) {
                foreach ($anggota as $registrasiId) {
                    $rootUntukRegistrasi[(int) $registrasiId] = (int) $rootId;
                }
            }

            // Tahap 2: ambil SELURUH billing milik episode agar total dan
            // status yang tampil tidak terpotong oleh filter pencarian.
            $billings = Billing::with(['pasien', 'registrasi', 'registrasiDetail.bagian', 'kelas'])
                ->aktif()
                ->whereIn('registrasi_id', $semuaAnggota)
                ->orderBy('registrasi_id')
                ->orderBy('billing_id')
                ->get();

            $registrasis = Registrasi::with('pasien')
                ->whereIn('registrasi_id', $semuaAnggota)
                ->get()
                ->keyBy('registrasi_id');

            $details = RegistrasiDetail::with('bagian')
                ->whereIn('registrasi_id', $semuaAnggota)
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->orderBy('registrasi_detail_id')
                ->get()
                ->groupBy('registrasi_id');

            $billings->groupBy(fn (Billing $billing) => $rootUntukRegistrasi[(int) $billing->registrasi_id] ?? (int) $billing->registrasi_id)
                ->each(function ($rows, $rootId) use ($registrasis, $details, &$episodes) {
                    $anggota = $rows
                        ->map(fn (Billing $billing) => $registrasis->get((int) $billing->registrasi_id))
                        ->filter()
                        ->unique('registrasi_id')
                        ->sortBy([
                            ['tgl_masuk', 'asc'],
                            ['registrasi_id', 'asc'],
                        ])
                        ->values();

                    $namaBagian = $anggota
                        ->flatMap(fn (Registrasi $registrasi) => $details->get($registrasi->registrasi_id, collect()))
                        ->map(fn (RegistrasiDetail $detail) => $detail->bagian?->nama_bagian)
                        ->filter()
                        ->unique()
                        ->values();

                    $statusIds = $rows->pluck('status_billing')->map(fn ($value) => (int) $value)->unique()->values();

                    $episodes->push((object) [
                        'root_id' => (int) $rootId,
                        'pasien' => $rows->first()->pasien,
                        'registrasis' => $anggota,
                        'jenis_rawat' => $anggota->pluck('jenis_rawat')->filter()->unique()->values(),
                        'bagian' => $namaBagian,
                        'billings' => $rows->sortBy('billing_id')->values(),
                        'status_ids' => $statusIds,
                        'tgl_masuk' => $anggota->min('tgl_masuk'),
                        'tgl_terakhir' => $rows->max('tgl_billing'),
                        'total' => (float) $rows->sum('total'),
                        'max_billing_id' => (int) $rows->max('billing_id'),
                    ]);
                });
        }

        if ($status !== null && $status !== '') {
            $statusInt = (int) $status;
            $episodes = $episodes
                ->filter(fn ($episode) => $episode->status_ids->contains($statusInt))
                ->values();
        }

        $episodes = $episodes->sortByDesc('max_billing_id')->values();
        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();

        $billing = new LengthAwarePaginator(
            $episodes->forPage($page, $perPage)->values(),
            $episodes->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        return view('moduls.Billing.Transaksi.DaftarBilling.daftar_billing', compact(
            'billing', 'cari', 'status', 'jenisRawat', 'dari', 'sampai'
        ));
    }
}
