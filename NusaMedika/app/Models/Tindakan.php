<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tindakan extends Model
{
    protected $table = 'tindakan';

    protected $primaryKey = 'tindakan_id';

    public $timestamps = false;

    protected $fillable = [
        'kode_tindakan',
        'nama_tindakan',
        'kategori_tindakan_id',
    ];

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('status_batal')->orWhere('status_batal', 0);
        });
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriTindakan::class, 'kategori_tindakan_id', 'kategori_tindakan_id');
    }

    /**
     * Group (panel pemeriksaan) yang memuat tindakan ini per unit penunjang.
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(GroupTindakan::class, 'group_tindakan_tindakan', 'tindakan_id', 'group_tindakan_id')
            ->wherePivot('status_batal', '!=', 1)
            ->where(
                fn ($q) => $q->where('group_tindakan.status_batal', '!=', 1)->orWhereNull('group_tindakan.status_batal')
            );
    }

    public function tarif(): HasMany
    {
        return $this->hasMany(Tarif::class, 'tindakan_id', 'tindakan_id')->aktif();
    }
}
