<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformOwnerSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('PLATFORM_OWNER_EMAIL', 'musa@neptuneware.com');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Platform Owner',
                'is_platform_owner' => true,
                // set a random password if it’s new
                'password' => Hash::make(env('PLATFORM_OWNER_PASSWORD', 'Qp4sud3_78kYs_t1')),
            ]
        );
    }
}
