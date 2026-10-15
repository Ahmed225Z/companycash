<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the single administrator account.
 *
 * Self-registration is disabled, so this seeder is the only way into a
 * fresh installation. The credentials below are for local development —
 * change the password before the system is deployed anywhere real.
 */
class AdminUserSeeder extends Seeder
{
    public const EMAIL = 'admin@xtreme.local';

    public const PASSWORD = '!zW7pQq86YUYY@WzSSNL';

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Xtreme Admin',
                'password' => Hash::make(self::PASSWORD),
            ],
        );
    }
}
