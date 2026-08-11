<?php

namespace App\Http\Resources\Identity;

use App\Models\AdminUser;
use App\Models\Customer;

final class AuthProfile
{
    /**
     * @return array{id: int, code: string, name: string, email: string, phone: ?string, status: string}
     */
    public static function customer(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'code' => (string) $customer->code,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'status' => $customer->status,
        ];
    }

    /**
     * @return array{id: int, code: string, name: string, email: string, status: string, roles: list<string>}
     */
    public static function admin(AdminUser $admin): array
    {
        return [
            'id' => $admin->id,
            'code' => (string) $admin->code,
            'name' => $admin->name,
            'email' => $admin->email,
            'status' => $admin->status,
            'roles' => $admin->getRoleNames()->values()->all(),
        ];
    }
}
