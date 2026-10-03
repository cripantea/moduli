<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email    = config('app.admin_email');
        $password = config('app.admin_password');

        if (! $email || ! $password) {
            throw new RuntimeException('Impostare ADMIN_EMAIL e ADMIN_PASSWORD nel .env prima di eseguire il seeder.');
        }

        User::firstOrCreate(
            ['email' => $email],
            [
                'name'              => 'Admin',
                'password'          => Hash::make($password),
                'role'              => 'superadmin',
                'tenant_id'         => null,
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
