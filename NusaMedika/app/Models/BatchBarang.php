<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchBarang extends Model
{
    protected $table = 'batch_barang';

    protected $primaryKey = 'id_batch';

    public $timestamps = false;

    protected $fillable = [
        'barang_id',
        'no_batch',
        'harga_beli',
        'harga_jual',
        'tgl_expired',
    ];

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('status_batal')->orWhere('status_batal', 0);
        });
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'id_batch', 'id_batch');
    }
}
