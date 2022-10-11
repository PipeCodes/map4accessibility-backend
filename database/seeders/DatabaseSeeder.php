<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            AccountStatusesTableSeeder::class,
            RolesAndPermissionsSeeder::class,
            UsersTableSeeder::class,
            LegalTextSeeder::class,
        ]);
    }
}
