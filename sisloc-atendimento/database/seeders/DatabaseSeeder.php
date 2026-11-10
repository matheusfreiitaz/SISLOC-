<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissaoSeeder::class,
            CadastrosBaseSeeder::class,
            DemonstracaoSeeder::class,
        ]);
    }
}
