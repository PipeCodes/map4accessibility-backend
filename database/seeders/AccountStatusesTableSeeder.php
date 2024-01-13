<?php

namespace Database\Seeders;

use App\Models\AccountStatus;
use Illuminate\Database\Seeder;

class AccountStatusesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AccountStatus::create([
            'id' => 1,
            'slug' => 'invited',
        ]);

        AccountStatus::create([
            'id' => 2,
            'slug' => 'active',
        ]);

        AccountStatus::create([
            'id' => 3,
            'slug' => 'blocked',
        ]);
    }
}
