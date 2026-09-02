<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('app.admin.email');
        $password = config('app.admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD belum diisi, seeder dilewati.');

            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('app.admin.name'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );
    }
}
