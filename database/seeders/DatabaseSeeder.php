<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Compte administrateur minimal, créé en base vide.
     */
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
