<?php

namespace App\Http\Controllers\RawatJalan\Pasien\ListPasienRajal;

use App\Helpers\EmrHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListPasienRajalController extends Controller
{
    public function index(Request $request)
    {

        if (count($request->query()) > 0) {
            $request->validate([
                'tanggal_kunjungan' => 'required|date',
                'poliklinik' => 'required|required',
                'dokter_id' => 'nullable|required',
            ]);
        }

        $tanggalKunjungan = $request->input('tanggal_kunjungan', date('Y-m-d'));
        $poliklinikId = $request->input('poliklinik', '');
        $dokter = $request->input('dokter_id', '');

        $listPasien = collect([]);

        if (! empty($tanggalKunjungan) && ! empty($poliklinikId)) {
            $query = DB::table('registrasi as r')
                ->select(
                    'r.prioritas',
                    'r.tgl_masuk',
                    'rd.registrasi_detail_id',
                    'p.no_mr',
                    'p.tgl_lahir',
                    'p.nama_pasien',
                    'b.nama_bagian',
                    'n.nama_nasabah',
                    'bt.status_selesai',
                    'ru.urutan'
                )
                ->join('registrasi_detail as rd', 'rd.registrasi_id', '=', 'r.registrasi_id')
                ->join('pasien as p', 'p.pasien_id', '=', 'r.pasien_id')
                ->join('bagian as b', 'b.bagian_id', '=', 'rd.bagian_id')
                ->join('pasien_nasabah as pn', 'pn.pasien_nasabah_id', '=', 'r.pasien_nasabah_id')
                ->join('nasabah as n', 'n.nasabah_id', '=', 'pn.nasabah_id')
                ->leftJoin('billing as bt', 'bt.registrasi_id', '=', 'r.registrasi_id')
                ->join('registrasi_urut as ru', 'ru.registrasi_detail_id', '=', 'rd.registrasi_detail_id')
                ->join('penanggung_rawat as pr', 'pr.registrasi_id', '=', 'r.registrasi_id')
                ->where(function ($q) {
                    $q->whereNull('rd.status_batal')->orWhere('rd.status_batal', 0);
                })
                ->where(function ($q) {
                    $q->whereNull('r.status_batal')->orWhere('r.status_batal', 0);
                })
                ->where(function ($q) {
                    $q->whereNull('p.status_batal')->orWhere('p.status_batal', 0);
                })
                ->where(function ($q) {
                    $q->whereNull('b.status_batal')->orWhere('b.status_batal', 0);
                })
                ->where(function ($q) {
                    $q->whereNull('pn.status_batal')->orWhere('pn.status_batal', 0);
                })
                ->where(function ($q) {
                    $q->whereNull('n.status_batal')->orWhere('n.status_batal', 0);
                })
                ->where(function ($q) {
                    $q->whereNull('bt.status_batal')->orWhere('bt.status_batal', 0);
                })
                ->where(function ($q) {
                    $q->whereNull('ru.status_batal')->orWhere('ru.status_batal', 0);
                })
                ->where(function ($q) {
                    $q->whereNull('pr.status_batal')->orWhere('pr.status_batal', 0);
                })
                ->whereDate('r.tgl_masuk', $tanggalKunjungan)
                ->where('rd.bagian_id', $poliklinikId)
                ->where('r.jenis_rawat', env('JENIS_RAWAT_RJ'));

            if (! empty($dokter)) {
                $query->where('pr.rawat_user_id', $dokter);
            }

            $listPasien = $query->orderBy('ru.urutan', 'asc')
                ->paginate(10)->withQueryString();

            if ($listPasien->isNotEmpty()) {
                $detailIds = $listPasien->pluck('registrasi_detail_id');

                $emrForms = DB::table('emr')
                    ->whereIn('registrasi_detail_id', $detailIds)
                    ->where(function ($q) {
                        $q->whereNull('status_batal')->orWhere('status_batal', 0);
                    })
                    ->whereIn('form_id', array_filter([
                        EmrHelper::formIdBySlug('pengkajian_awal_keperawatan'),
                        EmrHelper::formIdBySlug('pengkajian_harian_keperawatan'),
                        EmrHelper::formIdBySlug('soap'),
                    ]))
                    ->select('registrasi_detail_id', 'form_id')
                    ->get()
                    ->groupBy('registrasi_detail_id');

                $listPasien->getCollection()->transform(function ($item) use ($emrForms) {
                    $item->emr_forms = $emrForms->get($item->registrasi_detail_id, collect([]))->pluck('form_id')->toArray();

                    return $item;
                });
            }
        }

        return view('moduls.RawatJalan.Pasien.ListPasienRajal.list_pasien_rajal', compact(
            'listPasien',
            'tanggalKunjungan',
            'poliklinikId',
            'dokter'
        ));
    }
}
