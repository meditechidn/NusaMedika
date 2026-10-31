<?php

namespace App\Helpers;

use App\Models\SubMenu;
use Illuminate\Support\Facades\Auth;

/**
 * Gate aksi sensitif billing. Admin (user_id 1 / superadmin) selalu boleh;
 * user lain harus punya akses ke sub_menu yang relevan.
 */
class BillingAkses
{
    public const DAFTAR_BILLING = 'Billing/Transaksi/DaftarBilling/daftar_billing';

    public const BILLING_PASIEN = 'Billing/Transaksi/BillingPasien/billing_pasien';

    public const MASTER_TARIF = 'Administrator/ManajemenMaster/MasterTarif/master_tarif';

    public static function user()
    {
        return Auth::user();
    }

    public static function isAdmin(): bool
    {
        $user = static::user();

        return $user && ((int) $user->user_id === 1 || $user->user_name === 'superadmin');
    }

    public static function can(string $path): bool
    {
        if (static::isAdmin()) {
            return true;
        }

        $user = static::user();
        if (! $user) {
            return false;
        }

        $subMenuId = SubMenu::idByPath($path);

        return $subMenuId ? $user->hasSubMenuAccess((int) $subMenuId) : false;
    }

    public static function authorize(string $path): void
    {
        abort_unless(static::can($path), 403, 'Anda tidak memiliki akses untuk aksi billing ini.');
    }

    public static function authorizeAny(array $paths): void
    {
        $boleh = collect($paths)->contains(fn (string $path) => static::can($path));

        abort_unless($boleh, 403, 'Anda tidak memiliki akses untuk aksi billing ini.');
    }
}
