<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Executar seeders na ordem correta
        $this->call([
            StandSeeder::class,    // Primeiro criar stands
            UserSeeder::class,     // Depois usuários (que dependem dos stands)
            VehicleSeeder::class,  // Por último veículos (que dependem dos stands)
        ]);

        $this->command->info('🎉 Todos os dados de teste foram criados com sucesso!');
        $this->command->info('');
        $this->command->info('📊 Resumo dos dados criados:');
        $this->command->info('- 5 Stands automóveis');
        $this->command->info('- 10 Usuários (Admin, Managers, Sellers)');
        $this->command->info('- 12 Veículos (diferentes marcas e categorias)');
        $this->command->info('');
        $this->command->info('🔑 Credenciais de teste:');
        $this->command->info('- Admin: admin@autosync.pt / password123');
        $this->command->info('- Manager: joao.silva@autogaragemlisboa.pt / password123');
        $this->command->info('- Seller: ana.ferreira@autogaragemlisboa.pt / password123');
        $this->command->info('');
        $this->command->info('🌐 Teste a API em:');
        $this->command->info('- http://autosync-backend.test/api/vehicles');
        $this->command->info('- http://autosync-backend.test/api/stands');
        $this->command->info('- http://autosync-backend.test/api/health');
    }
}
