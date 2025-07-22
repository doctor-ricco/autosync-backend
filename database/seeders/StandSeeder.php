<?php

namespace Database\Seeders;

use App\Models\Stand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stands = [
            [
                'name' => 'AutoGaragem Lisboa',
                'slug' => 'auto-garagem-lisboa',
                'description' => 'Stand especializado em veículos usados de qualidade, com mais de 15 anos de experiência no mercado automóvel de Lisboa.',
                'address' => 'Rua das Flores, 123',
                'city' => 'Lisboa',
                'postal_code' => '1200-000',
                'phone' => '+351 213 456 789',
                'email' => 'info@autogaragemlisboa.pt',
                'website' => 'https://autogaragemlisboa.pt',
                'logo_url' => 'https://via.placeholder.com/300x100/2563eb/ffffff?text=AutoGaragem+Lisboa',
                'latitude' => 38.7223,
                'longitude' => -9.1393,
                'business_hours' => [
                    'monday' => ['09:00', '18:00'],
                    'tuesday' => ['09:00', '18:00'],
                    'wednesday' => ['09:00', '18:00'],
                    'thursday' => ['09:00', '18:00'],
                    'friday' => ['09:00', '18:00'],
                    'saturday' => ['09:00', '17:00'],
                    'sunday' => ['closed']
                ],
                'is_active' => true,
            ],
            [
                'name' => 'CarStand Porto',
                'slug' => 'carstand-porto',
                'description' => 'Stand moderno no coração do Porto, oferecendo uma vasta seleção de veículos seminovos e usados.',
                'address' => 'Avenida da Boavista, 456',
                'city' => 'Porto',
                'postal_code' => '4100-000',
                'phone' => '+351 225 789 123',
                'email' => 'contacto@carstandporto.pt',
                'website' => 'https://carstandporto.pt',
                'logo_url' => 'https://via.placeholder.com/300x100/dc2626/ffffff?text=CarStand+Porto',
                'latitude' => 41.1579,
                'longitude' => -8.6291,
                'business_hours' => [
                    'monday' => ['08:30', '19:00'],
                    'tuesday' => ['08:30', '19:00'],
                    'wednesday' => ['08:30', '19:00'],
                    'thursday' => ['08:30', '19:00'],
                    'friday' => ['08:30', '19:00'],
                    'saturday' => ['09:00', '18:00'],
                    'sunday' => ['10:00', '16:00']
                ],
                'is_active' => true,
            ],
            [
                'name' => 'AutoMercado Braga',
                'slug' => 'auto-mercado-braga',
                'description' => 'Stand familiar em Braga, com foco em veículos económicos e de qualidade para toda a família.',
                'address' => 'Rua do Comércio, 789',
                'city' => 'Braga',
                'postal_code' => '4700-000',
                'phone' => '+351 253 123 456',
                'email' => 'geral@automercadobraga.pt',
                'website' => 'https://automercadobraga.pt',
                'logo_url' => 'https://via.placeholder.com/300x100/059669/ffffff?text=AutoMercado+Braga',
                'latitude' => 41.5454,
                'longitude' => -8.4265,
                'business_hours' => [
                    'monday' => ['09:00', '18:30'],
                    'tuesday' => ['09:00', '18:30'],
                    'wednesday' => ['09:00', '18:30'],
                    'thursday' => ['09:00', '18:30'],
                    'friday' => ['09:00', '18:30'],
                    'saturday' => ['09:00', '17:30'],
                    'sunday' => ['closed']
                ],
                'is_active' => true,
            ],
            [
                'name' => 'LuxuryCars Coimbra',
                'slug' => 'luxury-cars-coimbra',
                'description' => 'Stand especializado em veículos de luxo e alta gama, oferecendo uma experiência premium aos nossos clientes.',
                'address' => 'Avenida Sá da Bandeira, 321',
                'city' => 'Coimbra',
                'postal_code' => '3000-000',
                'phone' => '+351 239 456 789',
                'email' => 'info@luxurycarscoimbra.pt',
                'website' => 'https://luxurycarscoimbra.pt',
                'logo_url' => 'https://via.placeholder.com/300x100/7c3aed/ffffff?text=LuxuryCars+Coimbra',
                'latitude' => 40.2033,
                'longitude' => -8.4103,
                'business_hours' => [
                    'monday' => ['10:00', '19:00'],
                    'tuesday' => ['10:00', '19:00'],
                    'wednesday' => ['10:00', '19:00'],
                    'thursday' => ['10:00', '19:00'],
                    'friday' => ['10:00', '19:00'],
                    'saturday' => ['10:00', '18:00'],
                    'sunday' => ['closed']
                ],
                'is_active' => true,
            ],
            [
                'name' => 'EcoVehicles Faro',
                'slug' => 'eco-vehicles-faro',
                'description' => 'Stand pioneiro em veículos elétricos e híbridos no Algarve, comprometido com a sustentabilidade.',
                'address' => 'Rua do Algarve, 654',
                'city' => 'Faro',
                'postal_code' => '8000-000',
                'phone' => '+351 289 789 123',
                'email' => 'contacto@ecovehiclesfaro.pt',
                'website' => 'https://ecovehiclesfaro.pt',
                'logo_url' => 'https://via.placeholder.com/300x100/16a34a/ffffff?text=EcoVehicles+Faro',
                'latitude' => 37.0193,
                'longitude' => -7.9304,
                'business_hours' => [
                    'monday' => ['09:00', '18:00'],
                    'tuesday' => ['09:00', '18:00'],
                    'wednesday' => ['09:00', '18:00'],
                    'thursday' => ['09:00', '18:00'],
                    'friday' => ['09:00', '18:00'],
                    'saturday' => ['09:00', '17:00'],
                    'sunday' => ['closed']
                ],
                'is_active' => true,
            ],
        ];

        foreach ($stands as $standData) {
            Stand::create($standData);
        }

        $this->command->info('Stands criados com sucesso!');
    }
}
