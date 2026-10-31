<?php

namespace App\Http\Controllers\GawatDarurat\Pasien\ListPasienGawatDarurat;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListPasienGawatDaruratController extends Controller
{
    public function index(Request $request)
    {
        $tanggalKunjungan = $request->input('tanggal_kunjungan', date('Y-m-d'));
        $zona = $request->input('zona', '');
        $triase = $request->input('triase', '');
        $dokterPegawaiId = $request->input('dokter_id', '');

        $dokterUserId = '';
        if (! empty($dokterPegawaiId)) {
            $dokterUserId = User::where('pegawai_id', $dokterPegawaiId)->value('user_id');
        }

        $listPasien = collect([]);

        if (! empty($tanggalKunjungan)) {
            $query = DB::table('registrasi as r')
                ->select(
                    'rd.registrasi_detail_id',
                    'r.tgl_masuk',
                    'p.no_mr',
                    'p.nama_pasien',
                    'p.tgl_lahir',
                    'p.jenis_kelamin',
                    'rd.triase',
                    'rd.lokasi_rawat',
                    'rd.cara_masuk',
                    'n.nama_nasabah',
                    'bt.status_selesai',
                    'rd.check_out',
                    'pg.nama_pegawai as dokter_jaga'
                )
                ->join('registrasi_detail as rd', 'rd.registrasi_id', '=', 'r.registrasi_id')
                ->join('pasien as p', 'p.pasien_id', '=', 'r.pasien_id')
                ->join('pasien_nasabah as pn', 'pn.pasien_nasabah_id', '=', 'r.pasien_nasabah_id')
                ->join('nasabah as n', 'n.nasabah_id', '=', 'pn.nasabah_id')
                ->leftJoin('billing as bt', 'bt.registrasi_id', '=', 'r.registrasi_id')
                ->leftJoin('penanggung_rawat as pr', 'pr.registrasi_id', '=', 'r.registrasi_id')
                ->leftJoin('users as u', 'u.user_id', '=', 'pr.rawat_user_id')
                ->leftJoin('pegawai as pg', 'pg.pegawai_id', '=', 'u.pegawai_id')
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
                    $q->whereNull('pn.status_batal')->orWhere('pn.status_batal', 0);
                })
                ->where(function ($q) {
                    $q->whereNull('n.status_batal')->orWhere('n.status_batal', 0);
                })
                ->where(function ($q) {
                    $q->whereNull('bt.status_batal')->orWhere('bt.status_batal', 0);
                })
                ->whereDate('r.tgl_masuk', $tanggalKunjungan)
                ->where('r.jenis_rawat', env('JENIS_RAWAT_IGD'));

            if (! empty($zona)) {
                $query->where('rd.lokasi_rawat', $zona);
            }

            if (! empty($triase)) {
                $query->where('r.prioritas', $triase);
            }

            if (! empty($dokterUserId)) {
                $query->where('pr.rawat_user_id', $dokterUserId);
            }

            $listPasien = $query->orderBy('r.tgl_masuk', 'asc')
                ->paginate(10)->withQueryString();
        }

        return view('moduls.GawatDarurat.Pasien.ListPasienGawatDarurat.list_pasien_gawat_darurat', compact(
            'listPasien',
            'tanggalKunjungan',
            'zona',
            'triase',
            'dokterPegawaiId'
        ));
    }
}
