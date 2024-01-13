<?php

namespace Database\Seeders;

use App\Models\LegalText;
use Illuminate\Database\Seeder;

class LegalTextSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        LegalText::create([
            'id' => 1,
            'type' => 'terms',
            'description' => '',
            'locale' => 'en',
        ]);

        LegalText::create([
            'id' => 2,
            'type' => 'privacy',
            'description' => '',
            'locale' => 'en',
        ]);
    }
}
