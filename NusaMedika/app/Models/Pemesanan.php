<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pemesanan extends Model
{
    protected $table = 'pemesanan';

    protected $primaryKey = 'pemesanan_id';

    public $timestamps = false;

    protected $fillable = [
        'no_pemesanan',
        'bagian_id',
        'tanggal_pemesanan',
        'status_pemesanan',
        'keterangan',
    ];

    public const STATUS_LABEL = [
        0 => 'Menunggu Persetujuan',
        1 => 'Disetujui',
        2 => 'Diterima',
        3 => 'Ditolak / Batal',
        4 => 'Proses Sebagian',
    ];

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('status_batal')->orWhere('status_batal', 0);
        });
    }

    public function bagian(): BelongsTo
    {
        return $this->belongsTo(Bagian::class, 'bagian_id', 'bagian_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PemesananDetail::class, 'pemesanan_id', 'pemesanan_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABEL[$this->status_pemesanan ?? 0] ?? '-';
    }
}
