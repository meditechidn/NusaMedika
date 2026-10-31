<?php

namespace App\Http\Controllers\RawatInap\Pasien\BedManagement;

use App\Helpers\BillingHelper;
use App\Http\Controllers\Controller;
use App\Models\Bagian;
use App\Models\Bed;
use App\Models\KelasRuang;
use App\Models\Registrasi;
use App\Models\RegistrasiDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BedManagementController extends Controller
{
    public function index(Request $request)
    {
        $ruangs = $this->ruangPerawatan();

        $ruangId = (int) $request->input('ruang_id');
        if (! $ruangs->contains('bagian_id', $ruangId)) {
            $ruangId = $ruangs->first()?->bagian_id ?? 0;
        }

        $beds = Bed::aktif()
            ->with('pasien')
            ->where('bagian_id', $ruangId)
            ->orderBy('no_kamar')
            ->orderBy('nama_bed')
            ->get();

        $kelasMap = KelasRuang::aktif()->pluck('nama_kelas_ruang', 'kelas_ruang_id');

        $terisiPasienIds = $beds->pluck('pasien_id_1')->filter()->unique();

        $bagianPerPasien = $beds->filter(fn ($b) => $b->pasien_id_1)
            ->mapWithKeys(fn ($b) => [$b->pasien_id_1 => (int) $b->bagian_id]);

        $registrasiMap = [];
        if ($terisiPasienIds->isNotEmpty()) {
            $registrasiMap = DB::table('registrasi as r')
                ->join('registrasi_detail as rd', 'rd.registrasi_id', '=', 'r.registrasi_id')
                ->leftJoin('penanggung_rawat as pr', 'pr.registrasi_id', '=', 'r.registrasi_id')
                ->leftJoin('users as u', 'u.user_id', '=', 'pr.rawat_user_id')
                ->leftJoin('pegawai as pg', 'pg.pegawai_id', '=', 'u.pegawai_id')
                ->whereIn('r.pasien_id', $terisiPasienIds->all())
                ->where('r.jenis_rawat', env('JENIS_RAWAT_RI', 'RI'))
                ->whereNull('r.tgl_keluar')
                ->whereNull('rd.check_out')
                ->where(function ($q) {
                    $q->whereNull('r.status_batal')->orWhere('r.status_batal', 0);
                })
                ->where(function ($q) {
                    $q->whereNull('rd.status_batal')->orWhere('rd.status_batal', 0);
                })
                ->select('r.pasien_id', 'rd.registrasi_detail_id', 'rd.bagian_id', 'pg.nama_pegawai as dpjp')
                ->orderByDesc('rd.registrasi_detail_id')
                ->get()
                ->groupBy('pasien_id')
                ->mapWithKeys(function ($rows) use ($bagianPerPasien) {
                    $rows = $rows->sortByDesc('registrasi_detail_id')->values();
                    $bagianId = $bagianPerPasien[$rows->first()->pasien_id] ?? null;
                    $row = ($bagianId ? $rows->firstWhere('bagian_id', $bagianId) : null) ?? $rows->first();

                    return [$row->pasien_id => $row];
                })
                ->all();
        }

        // Waitlist = pasien rawat inap dengan detail aktif di ruang ini yang belum menempati bed aktif mana pun
        $waitlist = DB::table('registrasi as r')
            ->join('registrasi_detail as rd', 'rd.registrasi_id', '=', 'r.registrasi_id')
            ->join('pasien as p', 'p.pasien_id', '=', 'r.pasien_id')
            ->leftJoin('pasien_nasabah as pn', 'pn.pasien_nasabah_id', '=', 'r.pasien_nasabah_id')
            ->leftJoin('nasabah as n', 'n.nasabah_id', '=', 'pn.nasabah_id')
            ->where('r.jenis_rawat', env('JENIS_RAWAT_RI', 'RI'))
            ->whereNull('r.tgl_keluar')
            ->where(function ($q) {
                $q->whereNull('r.status_batal')->orWhere('r.status_batal', 0);
            })
            ->whereNull('rd.check_out')
            ->where('rd.bagian_id', $ruangId)
            ->where(function ($q) {
                $q->whereNull('rd.status_batal')->orWhere('rd.status_batal', 0);
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('bed as bx')
                    ->whereColumn('bx.pasien_id_1', 'r.pasien_id')
                    ->where(function ($q) {
                        $q->whereNull('bx.status_batal')->orWhere('bx.status_batal', 0);
                    });
            })
            ->select(
                'r.registrasi_id',
                'rd.registrasi_detail_id',
                'r.tgl_masuk',
                'p.pasien_id',
                'p.no_mr',
                'p.nama_pasien',
                'n.nama_nasabah'
            )
            ->orderBy('r.tgl_masuk')
            ->orderBy('rd.registrasi_detail_id')
            ->get()
            ->groupBy('pasien_id')
            ->map(fn ($rows) => $rows->first())
            ->values();

        $kosongBeds = $beds->whereNull('pasien_id_1')->pluck('nama_bed', 'bed_id');

        $kosongBedsPerRuang = Bed::aktif()
            ->whereNull('pasien_id_1')
            ->orderBy('bagian_id')
            ->orderBy('no_kamar')
            ->orderBy('nama_bed')
            ->get(['bed_id', 'bagian_id', 'no_kamar', 'nama_bed'])
            ->groupBy('bagian_id')
            ->mapWithKeys(fn ($group, $bagianId) => [$bagianId => $group->mapWithKeys(
                fn ($b) => [$b->bed_id => $b->nama_bed.' (Kamar '.$b->no_kamar.')']
            )->all()])
            ->all();

        $permintaanPindah = DB::table('pindah_ruangan as pr')
            ->join('pasien as p', 'p.pasien_id', '=', 'pr.pasien_id')
            ->leftJoin('bed as b', 'b.bed_id', '=', 'pr.bed_tujuan_id')
            ->leftJoin('bagian as ba', 'ba.bagian_id', '=', 'pr.bagian_asal_id')
            ->where('pr.bagian_tujuan_id', $ruangId)
            ->where('pr.status', 0)
            ->where(function ($q) {
                $q->whereNull('pr.status_batal')->orWhere('pr.status_batal', 0);
            })
            ->select(
                'pr.pindah_ruangan_id',
                'pr.pasien_id',
                'pr.bed_tujuan_id',
                'pr.bagian_asal_id',
                'pr.input_time',
                'p.no_mr',
                'p.nama_pasien',
                'b.nama_bed',
                'b.no_kamar',
                'ba.nama_bagian as nama_bagian_asal'
            )
            ->orderBy('pr.input_time')
            ->get();

        $pendingPindah = DB::table('pindah_ruangan as pr')
            ->leftJoin('bagian as bg', 'bg.bagian_id', '=', 'pr.bagian_tujuan_id')
            ->where('pr.status', 0)
            ->where(function ($q) {
                $q->whereNull('pr.status_batal')->orWhere('pr.status_batal', 0);
            })
            ->select(
                'pr.pasien_id',
                'pr.pindah_ruangan_id',
                'pr.bed_tujuan_id',
                'bg.nama_bagian as nama_bagian_tujuan'
            )
            ->get()
            ->keyBy('pasien_id');

        return view('moduls.RawatInap.Pasien.BedManagement.bed_management', compact(
            'ruangs',
            'ruangId',
            'beds',
            'kelasMap',
            'registrasiMap',
            'waitlist',
            'kosongBeds',
            'kosongBedsPerRuang',
            'permintaanPindah',
            'pendingPindah'
        ));
    }

    public function assign(Request $request)
    {
        $request->validate([
            'bed_id' => 'required|integer|exists:bed,bed_id',
            'registrasi_detail_id' => 'required|integer|exists:registrasi_detail,registrasi_detail_id',
        ]);

        DB::beginTransaction();
        try {
            $bed = Bed::aktif()->where('bed_id', $request->bed_id)->firstOrFail();

            if ($bed->pasien_id_1) {
                return back()->with('error', 'Bed sudah terisi pasien lain.');
            }

            $detail = RegistrasiDetail::with('registrasi')
                ->where('registrasi_detail_id', $request->registrasi_detail_id)
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->firstOrFail();

            $registrasi = $detail->registrasi;

            if (! $registrasi || $registrasi->jenis_rawat !== env('JENIS_RAWAT_RI', 'RI')) {
                return back()->with('error', 'Registrasi rawat inap tidak ditemukan.');
            }

            $sudahPunyaBed = Bed::aktif()
                ->where('pasien_id_1', $registrasi->pasien_id)
                ->exists();

            if ($sudahPunyaBed) {
                return back()->with('error', 'Pasien sudah memiliki bed aktif pada ruang perawatan, tidak dapat ditempatkan lagi.');
            }

            $kelas = $detail->hak_kelas_id
                ? KelasRuang::aktif()->where('kelas_ruang_id', $detail->hak_kelas_id)->first()
                : null;

            $bed->pasien_id_1 = $registrasi->pasien_id;
            $bed->tgl_masuk = now();
            $bed->status_bed = 1;
            $bed->kelas_id = $kelas?->kelas_ruang_id ?? $bed->kelas_id;
            $bed->kodekelas = $kelas?->kelas_bpjs ?? $bed->kodekelas;
            $bed->namakelas = $kelas?->nama_kelas_ruang ?? $bed->namakelas;
            $bed->flag_persiapan_pulang = 2;
            $bed->tgl_pulang = null;
            $bed->mod_time = now();
            $bed->mod_user_id = Auth::id();
            $bed->save();

            BillingHelper::syncKamar($registrasi);

            DB::commit();

            return back()->with('success', 'Pasien berhasil ditempatkan ke bed.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menempatkan pasien: '.$e->getMessage());
        }
    }

    public function ready(Request $request)
    {
        $request->validate([
            'bed_id' => 'required|integer|exists:bed,bed_id',
            'tgl_pulang' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $bed = Bed::aktif()->where('bed_id', $request->bed_id)->whereNotNull('pasien_id_1')->firstOrFail();

            if (($bed->flag_persiapan_pulang ?? 0) === 1) {
                $bed->flag_persiapan_pulang = 2;
                $bed->tgl_pulang = null;
            } else {
                $bed->flag_persiapan_pulang = 1;
                $bed->tgl_pulang = $request->filled('tgl_pulang')
                    ? date('Y-m-d H:i:s', strtotime($request->tgl_pulang))
                    : now();
            }

            $bed->mod_time = now();
            $bed->mod_user_id = Auth::id();
            $bed->save();

            DB::commit();

            return back()->with('success', 'Status persiapan pulang bed diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memperbarui status bed: '.$e->getMessage());
        }
    }

    public function release(Request $request)
    {
        $request->validate([
            'bed_id' => 'required|integer|exists:bed,bed_id',
        ]);

        DB::beginTransaction();
        try {
            $bed = Bed::aktif()->where('bed_id', $request->bed_id)->whereNotNull('pasien_id_1')->firstOrFail();

            $this->kosongkanBed($bed);

            DB::commit();

            return back()->with('success', 'Pasien dilepas dari bed.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal melepas pasien: '.$e->getMessage());
        }
    }

    public function pulang(Request $request)
    {
        $request->validate([
            'bed_id' => 'required|integer|exists:bed,bed_id',
            'tgl_pulang' => 'required|date',
            'alasan_pulang' => 'nullable|string|max:255',
        ]);

        $tglKeluar = Carbon::parse($request->tgl_pulang);

        DB::beginTransaction();
        try {
            $bed = Bed::aktif()->where('bed_id', $request->bed_id)->whereNotNull('pasien_id_1')->firstOrFail();
            $pasienId = $bed->pasien_id_1;

            // Kosongkan bed
            $this->kosongkanBed($bed);

            // Selesaikan registrasi rawat inap aktif pasien
            $registrasi = DB::table('registrasi')
                ->where('pasien_id', $pasienId)
                ->where('jenis_rawat', env('JENIS_RAWAT_RI', 'RI'))
                ->whereNull('tgl_keluar')
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->orderBy('registrasi_id', 'desc')
                ->first();

            if ($registrasi) {
                DB::table('registrasi')
                    ->where('registrasi_id', $registrasi->registrasi_id)
                    ->update([
                        'tgl_keluar' => $tglKeluar,
                        'alasan_pulang' => $request->input('alasan_pulang'),
                        'mod_time' => now(),
                        'mod_user_id' => Auth::id(),
                    ]);

                $detailIds = DB::table('registrasi_detail')
                    ->where('registrasi_id', $registrasi->registrasi_id)
                    ->whereNull('check_out')
                    ->where(function ($q) {
                        $q->whereNull('status_batal')->orWhere('status_batal', 0);
                    })
                    ->pluck('registrasi_detail_id')
                    ->all();

                if ($detailIds) {
                    DB::table('registrasi_detail')
                        ->whereIn('registrasi_detail_id', $detailIds)
                        ->update([
                            'check_out' => $tglKeluar,
                            'mod_time' => now(),
                            'mod_user_id' => Auth::id(),
                        ]);

                }
            }

            BillingHelper::syncKamar(Registrasi::findOrFail($registrasi->registrasi_id));

            DB::table('billing')
                ->where('registrasi_id', $registrasi->registrasi_id)
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->update([
                    'status_selesai' => 1,
                    'mod_time' => now(),
                    'mod_user_id' => Auth::id(),
                ]);

            DB::commit();

            return back()->with('success', 'Pasien berhasil dipulangkan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memulangkan pasien: '.$e->getMessage());
        }
    }

    public function move(Request $request)
    {
        $request->validate([
            'bed_id' => 'required|integer|exists:bed,bed_id',
            'target_bed_id' => 'required|integer|exists:bed,bed_id',
            'ruang_id' => 'nullable|integer|exists:bagian,bagian_id',
        ]);

        if ($request->filled('ruang_id')) {
            return back()->with('error', 'Pindah lintas ruangan wajib melalui permintaan pindah ruangan.');
        }

        DB::beginTransaction();
        try {
            $current = Bed::aktif()->where('bed_id', $request->bed_id)->whereNotNull('pasien_id_1')->firstOrFail();

            if (($current->flag_persiapan_pulang ?? 0) === 1) {
                return back()->with('error', 'Pasien sedang dalam persiapan pulang, tidak dapat dipindah.');
            }

            $target = Bed::aktif()->where('bed_id', $request->target_bed_id)->whereNull('pasien_id_1')->firstOrFail();

            if ((int) $target->bagian_id !== (int) $current->bagian_id) {
                return back()->with('error', 'Pindah bed hanya untuk bed kosong pada ruangan yang sama. Gunakan Pindah Ruangan untuk lintas ruangan.');
            }

            $pasienId = $current->pasien_id_1;

            if ($this->punyaPermintaanPindah($pasienId)) {
                return back()->with('error', 'Pasien memiliki permintaan pindah ruangan yang belum diproses, tidak dapat dipindah.');
            }

            $tglPindah = now();

            // Isi bed tujuan dengan data pasien dari bed sebelumnya
            $target->pasien_id_1 = $pasienId;
            $target->tgl_masuk = $current->tgl_masuk;
            $target->status_bed = 1;
            $target->kelas_id = $current->kelas_id;
            $target->kodekelas = $current->kodekelas;
            $target->namakelas = $current->namakelas;
            $target->flag_persiapan_pulang = $current->flag_persiapan_pulang ?? 2;
            $target->tgl_pulang = $current->tgl_pulang;
            $target->mod_time = $tglPindah;
            $target->mod_user_id = Auth::id();
            $target->save();

            // Kosongkan bed sebelumnya
            $this->kosongkanBed($current, $tglPindah);

            BillingHelper::syncKamar(Registrasi::findOrFail($detailAktif->registrasi_id));

            // Catat pergerakan pasien di bed_log
            DB::table('bed_log')->insert([
                'pasien_id' => $pasienId,
                'registrasi_detail_id' => $this->detailIdUntukBed($pasienId, (int) $current->bagian_id),
                'bed_asal_id' => $current->bed_id,
                'bagian_asal_id' => $current->bagian_id,
                'bed_id' => $target->bed_id,
                'bagian_id' => $target->bagian_id,
                'aksi' => 'PINDAH_BED',
                'tgl_pindah' => $tglPindah,
                'input_time' => $tglPindah,
                'input_user_id' => Auth::id(),
            ]);

            DB::commit();

            return back()->with('success', 'Pasien berhasil dipindahkan ke bed tujuan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memindahkan pasien: '.$e->getMessage());
        }
    }

    public function pindahRequest(Request $request)
    {
        $request->validate([
            'bed_id' => 'required|integer|exists:bed,bed_id',
            'target_bed_id' => 'required|integer|exists:bed,bed_id',
            'ruang_id' => 'required|integer|exists:bagian,bagian_id',
        ]);

        $current = Bed::aktif()->where('bed_id', $request->bed_id)->whereNotNull('pasien_id_1')->firstOrFail();

        if (($current->flag_persiapan_pulang ?? 0) === 1) {
            return back()->with('error', 'Pasien sedang dalam persiapan pulang, tidak dapat dipindah.');
        }

        if ((int) $request->ruang_id === (int) $current->bagian_id) {
            return back()->with('error', 'Pilih ruangan yang berbeda dari ruangan saat ini.');
        }

        $target = Bed::aktif()->where('bed_id', $request->target_bed_id)->whereNull('pasien_id_1')->firstOrFail();

        if ((int) $target->bagian_id !== (int) $request->ruang_id) {
            return back()->with('error', 'Bed tujuan tidak berada pada ruangan terpilih.');
        }

        $pasienId = (int) $current->pasien_id_1;

        if ($this->punyaPermintaanPindah($pasienId)) {
            return back()->with('error', 'Pasien sudah memiliki permintaan pindah ruangan yang belum diproses.');
        }

        DB::table('pindah_ruangan')->insert([
            'pasien_id' => $pasienId,
            'registrasi_detail_id' => $this->detailIdUntukBed($pasienId, (int) $current->bagian_id),
            'bed_asal_id' => $current->bed_id,
            'bagian_asal_id' => $current->bagian_id,
            'bed_tujuan_id' => $target->bed_id,
            'bagian_tujuan_id' => (int) $request->ruang_id,
            'status' => 0,
            'input_time' => now(),
            'input_user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Permintaan pindah ruangan diajukan, menunggu persetujuan ruang tujuan.');
    }

    public function pindahApprove(Request $request)
    {
        $request->validate([
            'pindah_ruangan_id' => 'required|integer|exists:pindah_ruangan,pindah_ruangan_id',
        ]);

        DB::beginTransaction();
        try {
            $permintaan = DB::table('pindah_ruangan')
                ->where('pindah_ruangan_id', $request->pindah_ruangan_id)
                ->where('status', 0)
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->firstOrFail();

            $current = Bed::aktif()->where('bed_id', $permintaan->bed_asal_id)->whereNotNull('pasien_id_1')->firstOrFail();

            if ((int) $current->pasien_id_1 !== (int) $permintaan->pasien_id) {
                throw new \Exception('Pasien sudah tidak berada di bed asal.');
            }

            if (($current->flag_persiapan_pulang ?? 0) === 1) {
                throw new \Exception('Pasien sedang dalam persiapan pulang, tidak dapat dipindah.');
            }

            $target = Bed::aktif()->where('bed_id', $permintaan->bed_tujuan_id)->whereNull('pasien_id_1')->firstOrFail();

            if ((int) $target->bagian_id !== (int) $permintaan->bagian_tujuan_id) {
                throw new \Exception('Bed tujuan tidak lagi berada pada ruangan tujuan.');
            }

            $pasienId = (int) $current->pasien_id_1;
            $tglPindah = now();

            // Detail asal: utamakan detail yang sesuai ruangan bed asal
            $detailAktif = $this->detailAktifUntukBed($pasienId, (int) $current->bagian_id)
                ?? DB::table('registrasi_detail')
                    ->where('registrasi_detail_id', $permintaan->registrasi_detail_id)
                    ->first();

            if (! $detailAktif) {
                throw new \Exception('Registrasi detail asal tidak ditemukan.');
            }

            // Pastikan pasien hanya menempati satu bed (rapikan bed ganda dari data lama)
            $bedGanda = Bed::aktif()
                ->where('pasien_id_1', $pasienId)
                ->where('bed_id', '!=', $current->bed_id)
                ->get();

            foreach ($bedGanda as $bed) {
                $this->kosongkanBed($bed, $tglPindah);
            }

            // Isi bed tujuan dengan data pasien
            $target->pasien_id_1 = $pasienId;
            $target->tgl_masuk = $current->tgl_masuk;
            $target->status_bed = 1;
            $target->kelas_id = $current->kelas_id;
            $target->kodekelas = $current->kodekelas;
            $target->namakelas = $current->namakelas;
            $target->flag_persiapan_pulang = $current->flag_persiapan_pulang ?? 2;
            $target->tgl_pulang = $current->tgl_pulang;
            $target->mod_time = $tglPindah;
            $target->mod_user_id = Auth::id();
            $target->save();

            // Kosongkan bed asal
            $this->kosongkanBed($current, $tglPindah);

            // Tutup seluruh detail aktif pasien di semua ruang agar tidak muncul di waitlist ruang asal
            $detailIds = DB::table('registrasi_detail')
                ->where('registrasi_id', $detailAktif->registrasi_id)
                ->whereNull('check_out')
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->pluck('registrasi_detail_id')
                ->all();

            if ($detailIds) {
                DB::table('registrasi_detail')
                    ->whereIn('registrasi_detail_id', $detailIds)
                    ->update([
                        'check_out' => $tglPindah,
                        'mod_time' => $tglPindah,
                        'mod_user_id' => Auth::id(),
                    ]);
            }

            $registrasiDetailId = DB::table('registrasi_detail')->insertGetId([
                'registrasi_id' => $detailAktif->registrasi_id,
                'tgl_daftar' => $tglPindah,
                'check_in' => $tglPindah,
                'bagian_id' => $target->bagian_id,
                'bagian_asal_id' => $current->bagian_id,
                'kelas_id' => $detailAktif->kelas_id,
                'hak_kelas_id' => $detailAktif->hak_kelas_id,
                'input_time' => $tglPindah,
                'input_user_id' => Auth::id(),
            ]);

            // Catat pergerakan pasien di bed_log
            DB::table('bed_log')->insert([
                'pasien_id' => $pasienId,
                'registrasi_detail_id' => $registrasiDetailId,
                'bed_asal_id' => $current->bed_id,
                'bagian_asal_id' => $current->bagian_id,
                'bed_id' => $target->bed_id,
                'bagian_id' => $target->bagian_id,
                'aksi' => 'PINDAH_RUANGAN',
                'tgl_pindah' => $tglPindah,
                'input_time' => $tglPindah,
                'input_user_id' => Auth::id(),
            ]);

            // Tandai permintaan disetujui
            DB::table('pindah_ruangan')
                ->where('pindah_ruangan_id', $permintaan->pindah_ruangan_id)
                ->update([
                    'status' => 1,
                    'registrasi_detail_id' => $registrasiDetailId,
                    'disetujui_user_id' => Auth::id(),
                    'disetujui_time' => $tglPindah,
                    'mod_time' => $tglPindah,
                    'mod_user_id' => Auth::id(),
                ]);

            DB::commit();

            return back()->with('success', 'Permintaan pindah ruangan disetujui, pasien dipindahkan ke ruang tujuan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menyetujui pindah ruangan: '.$e->getMessage());
        }
    }

    public function pindahTolak(Request $request)
    {
        $request->validate([
            'pindah_ruangan_id' => 'required|integer|exists:pindah_ruangan,pindah_ruangan_id',
            'alasan_tolak' => 'nullable|string|max:255',
        ]);

        $updated = DB::table('pindah_ruangan')
            ->where('pindah_ruangan_id', $request->pindah_ruangan_id)
            ->where('status', 0)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->update([
                'status' => 2,
                'keterangan' => $request->input('alasan_tolak'),
                'mod_time' => now(),
                'mod_user_id' => Auth::id(),
            ]);

        if (! $updated) {
            return back()->with('error', 'Permintaan sudah diproses.');
        }

        return back()->with('success', 'Permintaan pindah ruangan ditolak.');
    }

    private function punyaPermintaanPindah(int $pasienId): bool
    {
        return DB::table('pindah_ruangan')
            ->where('pasien_id', $pasienId)
            ->where('status', 0)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->exists();
    }

    private function kosongkanBed(Bed $bed, $waktu = null): void
    {
        $bed->pasien_id_1 = null;
        $bed->pasien_id_2 = null;
        $bed->tgl_masuk = null;
        $bed->status_bed = 0;
        $bed->flag_persiapan_pulang = 2;
        $bed->tgl_pulang = null;
        $bed->mod_time = $waktu ?? now();
        $bed->mod_user_id = Auth::id();
        $bed->save();
    }

    private function detailAktifUntukBed(int $pasienId, int $bagianId)
    {
        return DB::table('registrasi_detail as rd')
            ->join('registrasi as r', 'r.registrasi_id', '=', 'rd.registrasi_id')
            ->where('r.pasien_id', $pasienId)
            ->where('r.jenis_rawat', env('JENIS_RAWAT_RI', 'RI'))
            ->whereNull('r.tgl_keluar')
            ->where('rd.bagian_id', $bagianId)
            ->whereNull('rd.check_out')
            ->where(function ($q) {
                $q->whereNull('r.status_batal')->orWhere('r.status_batal', 0);
            })
            ->where(function ($q) {
                $q->whereNull('rd.status_batal')->orWhere('rd.status_batal', 0);
            })
            ->orderByDesc('rd.registrasi_detail_id')
            ->select('rd.*')
            ->first();
    }

    private function detailIdUntukBed(int $pasienId, int $bagianId): ?int
    {
        return $this->detailAktifUntukBed($pasienId, $bagianId)?->registrasi_detail_id
            ?? $this->detailAktifId($pasienId);
    }

    private function detailAktifId(int $pasienId): ?int
    {
        $registrasi = DB::table('registrasi')
            ->where('pasien_id', $pasienId)
            ->where('jenis_rawat', env('JENIS_RAWAT_RI', 'RI'))
            ->whereNull('tgl_keluar')
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->orderByDesc('registrasi_id')
            ->first();

        if (! $registrasi) {
            return null;
        }

        return DB::table('registrasi_detail')
            ->where('registrasi_id', $registrasi->registrasi_id)
            ->whereNull('check_out')
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->orderByDesc('registrasi_detail_id')
            ->value('registrasi_detail_id');
    }

    private function ruangPerawatan()
    {
        return Bagian::where('referensi_bagian_id', env('REF_BAGIAN_RANAP', 2))
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->orderBy('bagian_id')
            ->get(['bagian_id', 'nama_bagian']);
    }
}
