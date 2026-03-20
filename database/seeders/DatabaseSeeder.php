<?php
// database/seeders/DatabaseSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Cria os 3 perfis de acesso
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'coordenador']);
        Role::firstOrCreate(['name' => 'consulta']); // ← novo perfil
    }
}
