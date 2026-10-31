<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registrasi extends Model
{
    protected $table = 'registrasi';

    protected $primaryKey = 'registrasi_id';

    public $timestamps = false;

    protected $fillable = [
        'pasien_id',
        'tgl_masuk',
        'jenis_rawat',
        'pasien_nasabah_id',
        'referensi_registrasi_id',
        'pasien_nasabah_id_2',
        'pasien_nasabah_id_3',
        'status_batal',
        'memo',
        'flag_online',
    ];

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'pasien_id', 'pasien_id');
    }

    public function registrasiDetails()
    {
        return $this->hasMany(RegistrasiDetail::class, 'registrasi_id', 'registrasi_id');
    }

    public function billings()
    {
        return $this->hasMany(Billing::class, 'registrasi_id', 'registrasi_id')
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            });
    }

    public function billing()
    {
        return $this->hasOne(Billing::class, 'registrasi_id', 'registrasi_id')
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            });
    }

    public function rujukanSep()
    {
        return $this->hasOne(RujukanSep::class, 'registrasi_id', 'registrasi_id')
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            });
    }

    public function pasienNasabah()
    {
        return $this->belongsTo(PasienNasabah::class, 'pasien_nasabah_id', 'pasien_nasabah_id');
    }

    public function penanggungRawat()
    {
        return $this->hasMany(PenanggungRawat::class, 'registrasi_id', 'registrasi_id')
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            });
    }
}
