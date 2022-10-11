<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $superAdmin = User::create([
            'id' => 1,
            'name' => 'Super Admin',
            'email' => 'dev@pipecodes.com',
            'password' => Hash::make('admin'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'account_status_id' => 2, // Active
        ]);

        $superAdmin->assignRole('super-admin');
    }
}
