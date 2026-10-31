<?php

namespace App\Http\Controllers\Registrasi\Pendaftaran\ListPelayananPasien;

use App\Helpers\BillingHelper;
use App\Http\Controllers\Controller;
use App\Models\DiagnosaRawat;
use App\Models\Pasien;
use App\Models\PenanggungRawat;
use App\Models\Registrasi;
use App\Models\RegistrasiUrut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListPelayananPasienController extends Controller
{
    public function index(Request $request)
    {
        $tanggalAwal = $request->input('tanggal_awal', date('Y-m-d'));
        $tanggalAkhir = $request->input('tanggal_akhir', date('Y-m-d'));
        $jenisLayanan = $request->input('jenis_layanan');
        $pasienId = $request->input('pasien_id');

        $query = Registrasi::with([
            'pasien' => function ($q) {
                $q->where(function ($sq) {
                    $sq->whereNull('status_batal')->orWhere('status_batal', 0);
                });
            },
            'registrasiDetails' => function ($q) {
                $q->where(function ($sq) {
                    $sq->whereNull('status_batal')->orWhere('status_batal', 0);
                })->with([
                    'bagian' => function ($sq) {
                        $sq->where(function ($sqq) {
                            $sqq->whereNull('status_batal')->orWhere('status_batal', 0);
                        });
                    },
                ]);
            },
            'billing',
            'rujukanSep',
            'pasienNasabah' => function ($q) {
                $q->where(function ($sq) {
                    $sq->whereNull('status_batal')->orWhere('status_batal', 0);
                })->with(['nasabah' => function ($sq) {
                    $sq->where(function ($sqq) {
                        $sqq->whereNull('status_batal')->orWhere('status_batal', 0);
                    });
                }]);
            },
            'penanggungRawat.user' => function ($q) {
                $q->where(function ($sq) {
                    $sq->whereNull('status_batal')->orWhere('status_batal', 0);
                });
            },
        ])
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            });

        // Filter Tanggal
        if ($tanggalAwal) {
            $query->whereDate('tgl_masuk', '>=', $tanggalAwal);
        }
        if ($tanggalAkhir) {
            $query->whereDate('tgl_masuk', '<=', $tanggalAkhir);
        }

        // Filter Jenis Layanan
        if ($jenisLayanan) {
            $query->where('jenis_rawat', $jenisLayanan);
        }

        // Filter Pasien
        if ($pasienId) {
            $query->where('pasien_id', $pasienId);
        }

        $kunjungan = $query->orderBy('tgl_masuk', 'desc')->paginate(15)->withQueryString();

        return view('moduls.Registrasi.Pendaftaran.ListPelayananPasien.list_pelayanan_pasien', compact(
            'kunjungan', 'tanggalAwal', 'tanggalAkhir', 'jenisLayanan', 'pasienId'
        ));
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $registrasi = Registrasi::findOrFail($id);
            $registrasi->status_batal = 1;
            $registrasi->mod_time = now();
            $registrasi->mod_user_id = auth()->id();
            $registrasi->save();

            // Cancel details
            foreach ($registrasi->registrasiDetails as $detail) {
                $detail->status_batal = 1;
                $detail->mod_time = now();
                $detail->mod_user_id = auth()->id();
                $detail->save();

                // Cancel registrasi_urut terkait
                RegistrasiUrut::where('registrasi_detail_id', $detail->registrasi_detail_id)
                    ->where(function ($q) {
                        $q->whereNull('status_batal')->orWhere('status_batal', 0);
                    })
                    ->update([
                        'status_batal' => 1,
                        'mod_time' => now(),
                        'mod_user_id' => auth()->id(),
                    ]);
            }

            // Cancel diagnosa_rawat & penanggung_rawat terkait
            DiagnosaRawat::where('registrasi_id', $registrasi->registrasi_id)
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->update([
                    'status_batal' => 1,
                    'mod_time' => now(),
                    'mod_user_id' => auth()->id(),
                ]);

            PenanggungRawat::where('registrasi_id', $registrasi->registrasi_id)
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->update([
                    'status_batal' => 1,
                    'mod_time' => now(),
                    'mod_user_id' => auth()->id(),
                ]);

            BillingHelper::voidRegistrasi((int) $registrasi->registrasi_id, 'Registrasi dibatalkan');

            DB::commit();

            return redirect()->back()->with('success', 'Layanan berhasil dihapus/dibatalkan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal membatalkan layanan: '.$e->getMessage());
        }
    }
}
