<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Stand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar stands para associar aos usuários
        $stands = Stand::all();

        $users = [
            // Admin principal
            [
                'name' => 'Admin Principal',
                'email' => 'admin@autosync.pt',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'phone' => '+351 213 123 456',
                'avatar_url' => 'https://via.placeholder.com/150/2563eb/ffffff?text=Admin',
                'stand_id' => null, // Admin não está associado a um stand específico
                'commission_rate' => 0,
                'is_active' => true,
            ],
            // Managers dos stands
            [
                'name' => 'João Silva',
                'email' => 'joao.silva@autogaragemlisboa.pt',
                'password' => Hash::make('password123'),
                'role' => 'manager',
                'phone' => '+351 213 456 789',
                'avatar_url' => 'https://via.placeholder.com/150/dc2626/ffffff?text=Joao',
                'stand_id' => $stands->where('name', 'AutoGaragem Lisboa')->first()->id,
                'commission_rate' => 5.00,
                'is_active' => true,
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'maria.santos@carstandporto.pt',
                'password' => Hash::make('password123'),
                'role' => 'manager',
                'phone' => '+351 225 789 123',
                'avatar_url' => 'https://via.placeholder.com/150/059669/ffffff?text=Maria',
                'stand_id' => $stands->where('name', 'CarStand Porto')->first()->id,
                'commission_rate' => 5.00,
                'is_active' => true,
            ],
            [
                'name' => 'Pedro Costa',
                'email' => 'pedro.costa@automercadobraga.pt',
                'password' => Hash::make('password123'),
                'role' => 'manager',
                'phone' => '+351 253 123 456',
                'avatar_url' => 'https://via.placeholder.com/150/7c3aed/ffffff?text=Pedro',
                'stand_id' => $stands->where('name', 'AutoMercado Braga')->first()->id,
                'commission_rate' => 5.00,
                'is_active' => true,
            ],
            // Sellers
            [
                'name' => 'Ana Ferreira',
                'email' => 'ana.ferreira@autogaragemlisboa.pt',
                'password' => Hash::make('password123'),
                'role' => 'seller',
                'phone' => '+351 213 456 790',
                'avatar_url' => 'https://via.placeholder.com/150/16a34a/ffffff?text=Ana',
                'stand_id' => $stands->where('name', 'AutoGaragem Lisboa')->first()->id,
                'commission_rate' => 3.50,
                'is_active' => true,
            ],
            [
                'name' => 'Carlos Oliveira',
                'email' => 'carlos.oliveira@autogaragemlisboa.pt',
                'password' => Hash::make('password123'),
                'role' => 'seller',
                'phone' => '+351 213 456 791',
                'avatar_url' => 'https://via.placeholder.com/150/ea580c/ffffff?text=Carlos',
                'stand_id' => $stands->where('name', 'AutoGaragem Lisboa')->first()->id,
                'commission_rate' => 3.00,
                'is_active' => true,
            ],
            [
                'name' => 'Sofia Martins',
                'email' => 'sofia.martins@carstandporto.pt',
                'password' => Hash::make('password123'),
                'role' => 'seller',
                'phone' => '+351 225 789 124',
                'avatar_url' => 'https://via.placeholder.com/150/be123c/ffffff?text=Sofia',
                'stand_id' => $stands->where('name', 'CarStand Porto')->first()->id,
                'commission_rate' => 4.00,
                'is_active' => true,
            ],
            [
                'name' => 'Miguel Rodrigues',
                'email' => 'miguel.rodrigues@carstandporto.pt',
                'password' => Hash::make('password123'),
                'role' => 'seller',
                'phone' => '+351 225 789 125',
                'avatar_url' => 'https://via.placeholder.com/150/0891b2/ffffff?text=Miguel',
                'stand_id' => $stands->where('name', 'CarStand Porto')->first()->id,
                'commission_rate' => 3.50,
                'is_active' => true,
            ],
            [
                'name' => 'Inês Pereira',
                'email' => 'ines.pereira@automercadobraga.pt',
                'password' => Hash::make('password123'),
                'role' => 'seller',
                'phone' => '+351 253 123 457',
                'avatar_url' => 'https://via.placeholder.com/150/9333ea/ffffff?text=Ines',
                'stand_id' => $stands->where('name', 'AutoMercado Braga')->first()->id,
                'commission_rate' => 3.00,
                'is_active' => true,
            ],
            // Viewers (usuários com acesso limitado)
            [
                'name' => 'Ricardo Alves',
                'email' => 'ricardo.alves@autosync.pt',
                'password' => Hash::make('password123'),
                'role' => 'viewer',
                'phone' => '+351 213 123 457',
                'avatar_url' => 'https://via.placeholder.com/150/64748b/ffffff?text=Ricardo',
                'stand_id' => null,
                'commission_rate' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        $this->command->info('Usuários criados com sucesso!');
        $this->command->info('Credenciais de teste:');
        $this->command->info('- Admin: admin@autosync.pt / password123');
        $this->command->info('- Manager: joao.silva@autogaragemlisboa.pt / password123');
        $this->command->info('- Seller: ana.ferreira@autogaragemlisboa.pt / password123');
    }
}
