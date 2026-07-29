<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IdentityDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = AdminUser::query()->firstOrCreate(
            ['email' => 'super_admin@example.com'],
            [
                'code' => (string) Str::ulid(),
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'password' => 'password',
                'status' => AdminUser::STATUS_ACTIVE,
            ],
        );
        $admin->assignRole('super_admin');

        Customer::query()->firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'code' => (string) Str::ulid(),
                'name' => 'Demo Customer',
                'phone' => '0900000001',
                'password' => 'password',
                'status' => Customer::STATUS_ACTIVE,
            ],
        );
    }
}
