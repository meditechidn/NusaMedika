<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingDetail extends Model
{
    protected $table = 'billing_detail';

    protected $primaryKey = 'billing_detail_id';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public const PENDING = 0;

    public const POSTED = 1;

    public const VOID = 2;

    public const STATUS_LABEL = [
        self::PENDING => 'Tertunda',
        self::POSTED => 'Tercatat',
        self::VOID => 'Dibatalkan',
    ];

    public const JENIS_LABEL = [
        'ADMINISTRASI' => 'Administrasi',
        'TINDAKAN' => 'Tindakan',
        'OBAT' => 'Obat',
        'BMHP' => 'BMHP',
        'SUKET' => 'Surat Keterangan',
        'ORDER_LAB' => 'Laboratorium',
        'ORDER_RAD' => 'Radiologi',
        'ORDER_PATOLOGI' => 'Patologi',
        'ORDER_MIKROBIOLOGI' => 'Mikrobiologi',
        'ORDER_POCT' => 'POCT',
        'RESEP' => 'Obat',
        'TINDAKAN_MEDIS' => 'Tindakan Medis',
        'KAMAR' => 'Kamar',
        'VISITE' => 'Visite',
        'KONSULTASI' => 'Konsultasi',
        'PAKET' => 'Paket Tindakan',
        'MANUAL' => 'Manual',
    ];

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('status_batal')->orWhere('status_batal', 0);
        });
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->aktif()->where('status', self::POSTED);
    }

    public function billing(): BelongsTo
    {
        return $this->belongsTo(Billing::class, 'billing_id', 'billing_id');
    }

    public function registrasiDetail(): BelongsTo
    {
        return $this->belongsTo(RegistrasiDetail::class, 'registrasi_detail_id', 'registrasi_detail_id');
    }

    public function getJenisLabelAttribute(): string
    {
        return self::JENIS_LABEL[$this->jenis_layanan] ?? $this->jenis_layanan;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABEL[$this->status] ?? 'Tidak Diketahui';
    }
}
