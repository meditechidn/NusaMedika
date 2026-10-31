<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Billing extends Model
{
    protected $table = 'billing';

    protected $primaryKey = 'billing_id';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'tgl_billing' => 'datetime',
        'tgl_masuk' => 'datetime',
        'tgl_keluar' => 'datetime',
        'closed_at' => 'datetime',
        'void_at' => 'datetime',
    ];

    public const OPEN = 1;

    public const CLOSED = 3;

    public const VOID = 9;

    public const STATUS_LABEL = [
        self::OPEN => 'Terbuka',
        self::CLOSED => 'Ditutup',
        self::VOID => 'Dibatalkan',
    ];

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('status_batal')->orWhere('status_batal', 0);
        });
    }

    public function scopeTerbuka(Builder $query): Builder
    {
        return $query->aktif()->where('status_billing', self::OPEN);
    }

    public function registrasi(): BelongsTo
    {
        return $this->belongsTo(Registrasi::class, 'registrasi_id', 'registrasi_id');
    }

    public function registrasiDetail(): BelongsTo
    {
        return $this->belongsTo(RegistrasiDetail::class, 'registrasi_detail_id', 'registrasi_detail_id');
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'pasien_id', 'pasien_id');
    }

    public function nasabah(): BelongsTo
    {
        return $this->belongsTo(Nasabah::class, 'nasabah_id', 'nasabah_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(KelasRuang::class, 'kelas_ruang_id', 'kelas_ruang_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(BillingDetail::class, 'billing_id', 'billing_id')->where(function ($q) {
            $q->whereNull('status_batal')->orWhere('status_batal', 0);
        });
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABEL[$this->status_billing] ?? ($this->status_selesai ? 'Ditutup' : 'Terbuka');
    }

    public function getIsTerbukaAttribute(): bool
    {
        return (int) $this->status_billing === self::OPEN;
    }
}
