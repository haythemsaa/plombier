<?php

namespace Database\Seeders;

use App\Models\LoyaltyTier;
use Illuminate\Database\Seeder;

class LoyaltyTiersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Bronze',
                'level' => 1,
                'min_points_required' => 0,
                'min_bookings_required' => 0,
                'discount_percentage' => 0,
                'priority_support' => 0,
                'benefits' => [
                    'Accès à tous les services',
                    'Support client standard',
                    'Historique des réservations',
                ],
                'badge_color' => '#CD7F32',
                'badge_icon' => 'bronze_badge',
            ],
            [
                'name' => 'Silver',
                'level' => 2,
                'min_points_required' => 500,
                'min_bookings_required' => 5,
                'discount_percentage' => 5.00,
                'priority_support' => 1,
                'benefits' => [
                    'Tous les avantages Bronze',
                    '5% de réduction sur tous les services',
                    'Support client prioritaire',
                    'Annulation gratuite jusqu\'à 6h avant',
                    'Badge Silver sur le profil',
                ],
                'badge_color' => '#C0C0C0',
                'badge_icon' => 'silver_badge',
            ],
            [
                'name' => 'Gold',
                'level' => 3,
                'min_points_required' => 1500,
                'min_bookings_required' => 15,
                'discount_percentage' => 10.00,
                'priority_support' => 2,
                'benefits' => [
                    'Tous les avantages Silver',
                    '10% de réduction sur tous les services',
                    'Support client VIP 24/7',
                    'Annulation gratuite jusqu\'à 3h avant',
                    'Accès prioritaire aux nouveaux services',
                    'Un service gratuit à votre anniversaire',
                    'Badge Gold sur le profil',
                ],
                'badge_color' => '#FFD700',
                'badge_icon' => 'gold_badge',
            ],
            [
                'name' => 'Platinum',
                'level' => 4,
                'min_points_required' => 3000,
                'min_bookings_required' => 30,
                'discount_percentage' => 15.00,
                'priority_support' => 3,
                'benefits' => [
                    'Tous les avantages Gold',
                    '15% de réduction sur tous les services',
                    'Manager de compte dédié',
                    'Annulation gratuite jusqu\'à 1h avant',
                    'Accès exclusif aux services premium',
                    'Surclassement gratuit quand disponible',
                    'Deux services gratuits par an',
                    'Invitation à des événements exclusifs',
                    'Badge Platinum sur le profil',
                ],
                'badge_color' => '#E5E4E2',
                'badge_icon' => 'platinum_badge',
            ],
        ];

        foreach ($tiers as $tier) {
            LoyaltyTier::updateOrCreate(
                ['level' => $tier['level']],
                $tier
            );
        }

        $this->command->info('Loyalty tiers created successfully!');
    }
}
