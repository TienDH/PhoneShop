<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UsersSeeder extends Seeder
{
    public function run()
    {
        $email = trim((string) config('seeders.user.email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('SEED_USER_EMAIL must be a valid email address.');
        }

        $user = User::firstOrNew(['email' => $email]);
        if ($user->exists) {
            return;
        }

        $password = (string) config('seeders.user.password');
        if ($password === '' && app()->environment(['local', 'testing'])) {
            $password = '12345678';
        }
        if (strlen($password) < 8 || (!app()->environment(['local', 'testing']) && $password === '12345678')) {
            throw new RuntimeException('Set SEED_USER_PASSWORD to a private password of at least 8 characters before creating the demo user.');
        }

        // Only this explicitly seeded demo account skips email confirmation.
        $user->forceFill([
            'name' => 'TDH Customer',
            'password' => Hash::make($password),
            'role' => 'user',
            'email_verified_at' => now(),
        ])->save();
    }
}
