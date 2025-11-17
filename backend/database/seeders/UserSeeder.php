<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Provider;
use App\Models\Address;
use App\Models\ProviderService;
use App\Models\ProviderZone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test client
        $clientUser = User::create([
            'id' => Str::uuid(),
            'type' => 'client',
            'first_name' => 'Ahmed',
            'last_name' => 'Ben Ali',
            'phone' => '+216 98 123 456',
            'email' => 'client@servicehub.tn',
            'password' => bcrypt('password'),
            'language' => 'fr',
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);

        Client::create([
            'user_id' => $clientUser->id,
            'subscription_type' => 'free',
            'loyalty_points' => 150,
        ]);

        // Create client addresses
        Address::create([
            'user_id' => $clientUser->id,
            'label' => 'Maison',
            'street' => '123 Avenue Habib Bourguiba',
            'city' => 'Tunis',
            'governorate' => 'Tunis',
            'postal_code' => '1000',
            'latitude' => 36.8065,
            'longitude' => 10.1815,
            'is_default' => true,
        ]);

        Address::create([
            'user_id' => $clientUser->id,
            'label' => 'Bureau',
            'street' => '45 Rue de la République',
            'city' => 'Lac 2',
            'governorate' => 'Tunis',
            'latitude' => 36.8625,
            'longitude' => 10.2306,
            'is_default' => false,
        ]);

        // Create test providers
        $this->createProvider(
            'Mohamed',
            'Trabelsi',
            '+216 98 234 567',
            'plombier@servicehub.tn',
            'Plomberie Pro',
            '12345678',
            10,
            [1], // Plomberie
            'Tunis',
            4.7,
            48
        );

        $this->createProvider(
            'Karim',
            'Gharbi',
            '+216 98 345 678',
            'electricien@servicehub.tn',
            'Électricité Expert',
            '23456789',
            8,
            [2], // Électricité
            'Ariana',
            4.5,
            32
        );

        $this->createProvider(
            'Fatma',
            'Mansour',
            '+216 98 456 789',
            'menage@servicehub.tn',
            'Nettoyage Premium',
            '34567890',
            5,
            [5, 6], // Ménage + Nettoyage approfondi
            'Tunis',
            4.8,
            65
        );

        $this->createProvider(
            'Salah',
            'Ben Salah',
            '+216 98 567 890',
            'clim@servicehub.tn',
            'Climatisation Services',
            '45678901',
            12,
            [3], // Climatisation
            'Sousse',
            4.6,
            28
        );

        $this->createProvider(
            'Leila',
            'Hamdi',
            '+216 98 678 901',
            'babysitting@servicehub.tn',
            null,
            '56789012',
            3,
            [8], // Babysitting
            'Tunis',
            4.9,
            52
        );

        $this->command->info('Users and providers seeded successfully!');
    }

    private function createProvider(
        $firstName,
        $lastName,
        $phone,
        $email,
        $businessName,
        $cin,
        $yearsExperience,
        $serviceIds,
        $governorate,
        $rating = 0,
        $ratingCount = 0
    ) {
        $providerUser = User::create([
            'id' => Str::uuid(),
            'type' => 'provider',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'email' => $email,
            'password' => bcrypt('password'),
            'language' => 'fr',
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);

        $provider = Provider::create([
            'user_id' => $providerUser->id,
            'business_name' => $businessName,
            'cin' => $cin,
            'years_experience' => $yearsExperience,
            'status' => 'active',
            'verified_at' => now(),
            'subscription_type' => 'pro',
            'rating_average' => $rating,
            'rating_count' => $ratingCount,
            'completion_rate' => rand(90, 100),
            'response_time_avg' => rand(300, 1800),
        ]);

        // Add services
        foreach ($serviceIds as $serviceId) {
            ProviderService::create([
                'provider_id' => $provider->id,
                'service_id' => $serviceId,
                'price' => rand(40, 80),
                'experience_years' => $yearsExperience,
                'is_available' => true,
            ]);
        }

        // Add zones
        ProviderZone::create([
            'provider_id' => $provider->id,
            'governorate' => $governorate,
            'cities' => null,
            'radius_km' => 15,
        ]);

        return $provider;
    }
}
