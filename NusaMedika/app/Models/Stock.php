<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $table = 'stock';

    protected $primaryKey = 'stock_id';

    public $timestamps = false;

    protected $fillable = [
        'barang_id',
        'no_batch',
        'bagian_id',
        'jumlah',
        'id_batch',
    ];

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('status_batal')->orWhere('status_batal', 0);
        });
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id', 'barang_id');
    }

    public function bagian(): BelongsTo
    {
        return $this->belongsTo(Bagian::class, 'bagian_id', 'bagian_id');
    }

    public function batchBarang(): BelongsTo
    {
        return $this->belongsTo(BatchBarang::class, 'id_batch', 'id_batch');
    }
}
