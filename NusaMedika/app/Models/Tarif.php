<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tarif tindakan per kelas perawatan.
 *
 * Satu baris per (tindakan, kelas). `kelas_ruang_id` NULL = tarif default yang
 * dipakai bila kelas spesifik belum diisi. Kelas perawatan (Kelas 1/2/3/VIP/
 * VVIP) berasal dari master `kelas_ruang`, bukan kolom terpisah di sini.
 */
class Tarif extends Model
{
    protected $table = 'tarif';

    protected $primaryKey = 'tarif_id';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'tarif' => 'float',
        'jasa_sarana' => 'float',
        'jasa_medis' => 'float',
        'bhp' => 'float',
    ];

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('status_batal')->orWhere('status_batal', 0);
        });
    }

    public function tindakan(): BelongsTo
    {
        return $this->belongsTo(Tindakan::class, 'tindakan_id', 'tindakan_id');
    }

    public function kelasRuang(): BelongsTo
    {
        return $this->belongsTo(KelasRuang::class, 'kelas_ruang_id', 'kelas_ruang_id');
    }
}
