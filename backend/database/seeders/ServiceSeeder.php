<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create categories
        $maintenance = ServiceCategory::create([
            'name_ar' => 'خدمات الصيانة',
            'name_fr' => 'Services de maintenance',
            'icon' => '🔧',
            'color' => '#2196F3',
            'order' => 1,
            'is_active' => true,
        ]);

        $cleaning = ServiceCategory::create([
            'name_ar' => 'خدمات التنظيف',
            'name_fr' => 'Services de nettoyage',
            'icon' => '🧹',
            'color' => '#4CAF50',
            'order' => 2,
            'is_active' => true,
        ]);

        $care = ServiceCategory::create([
            'name_ar' => 'خدمات الرعاية',
            'name_fr' => 'Services de garde et assistance',
            'icon' => '👶',
            'color' => '#FF9800',
            'order' => 3,
            'is_active' => true,
        ]);

        $transport = ServiceCategory::create([
            'name_ar' => 'خدمات النقل',
            'name_fr' => 'Services de transport et logistique',
            'icon' => '🚚',
            'color' => '#9C27B0',
            'order' => 4,
            'is_active' => true,
        ]);

        $beauty = ServiceCategory::create([
            'name_ar' => 'خدمات التجميل',
            'name_fr' => 'Services de beauté à domicile',
            'icon' => '💅',
            'color' => '#E91E63',
            'order' => 5,
            'is_active' => true,
        ]);

        // Maintenance services
        Service::create([
            'category_id' => $maintenance->id,
            'name_ar' => 'سباكة',
            'name_fr' => 'Plomberie',
            'description_ar' => 'إصلاح وتركيب الأنابيب والتجهيزات الصحية',
            'description_fr' => 'Réparation et installation de plomberie',
            'icon' => '🚰',
            'unit' => 'hour',
            'base_price' => 50.00,
            'commission_rate' => 18.00,
            'is_active' => true,
            'order' => 1,
        ]);

        Service::create([
            'category_id' => $maintenance->id,
            'name_ar' => 'كهرباء',
            'name_fr' => 'Électricité',
            'description_ar' => 'تركيب وإصلاح الأنظمة الكهربائية',
            'description_fr' => 'Installation et réparation électrique',
            'icon' => '⚡',
            'unit' => 'hour',
            'base_price' => 50.00,
            'commission_rate' => 18.00,
            'is_active' => true,
            'order' => 2,
        ]);

        Service::create([
            'category_id' => $maintenance->id,
            'name_ar' => 'تكييف الهواء',
            'name_fr' => 'Climatisation',
            'description_ar' => 'تركيب وصيانة أجهزة التكييف',
            'description_fr' => 'Installation et maintenance de climatisation',
            'icon' => '❄️',
            'unit' => 'task',
            'base_price' => 50.00,
            'commission_rate' => 18.00,
            'is_active' => true,
            'order' => 3,
        ]);

        Service::create([
            'category_id' => $maintenance->id,
            'name_ar' => 'نجارة',
            'name_fr' => 'Menuiserie',
            'description_ar' => 'أعمال النجارة والتركيب',
            'description_fr' => 'Travaux de menuiserie et installation',
            'icon' => '🪚',
            'unit' => 'hour',
            'base_price' => 45.00,
            'commission_rate' => 18.00,
            'is_active' => true,
            'order' => 4,
        ]);

        // Cleaning services
        Service::create([
            'category_id' => $cleaning->id,
            'name_ar' => 'تنظيف منزلي',
            'name_fr' => 'Ménage régulier',
            'description_ar' => 'تنظيف المنزل الشامل',
            'description_fr' => 'Nettoyage complet de la maison',
            'icon' => '🏠',
            'unit' => 'hour',
            'base_price' => 25.00,
            'commission_rate' => 20.00,
            'is_active' => true,
            'order' => 1,
        ]);

        Service::create([
            'category_id' => $cleaning->id,
            'name_ar' => 'تنظيف عميق',
            'name_fr' => 'Nettoyage approfondi',
            'description_ar' => 'تنظيف شامل ومفصل',
            'description_fr' => 'Nettoyage en profondeur détaillé',
            'icon' => '✨',
            'unit' => 'hour',
            'base_price' => 35.00,
            'commission_rate' => 20.00,
            'is_active' => true,
            'order' => 2,
        ]);

        Service::create([
            'category_id' => $cleaning->id,
            'name_ar' => 'تنظيف النوافذ',
            'name_fr' => 'Nettoyage de vitres',
            'description_ar' => 'تنظيف النوافذ والزجاج',
            'description_fr' => 'Nettoyage des fenêtres et vitres',
            'icon' => '🪟',
            'unit' => 'sqm',
            'base_price' => 3.00,
            'commission_rate' => 20.00,
            'is_active' => true,
            'order' => 3,
        ]);

        // Care services
        Service::create([
            'category_id' => $care->id,
            'name_ar' => 'رعاية الأطفال',
            'name_fr' => 'Babysitting',
            'description_ar' => 'رعاية وحضانة الأطفال',
            'description_fr' => 'Garde d\'enfants à domicile',
            'icon' => '👶',
            'unit' => 'hour',
            'base_price' => 15.00,
            'commission_rate' => 15.00,
            'is_active' => true,
            'order' => 1,
        ]);

        Service::create([
            'category_id' => $care->id,
            'name_ar' => 'رعاية كبار السن',
            'name_fr' => 'Garde de personnes âgées',
            'description_ar' => 'رعاية ومساعدة كبار السن',
            'description_fr' => 'Assistance aux personnes âgées',
            'icon' => '👵',
            'unit' => 'hour',
            'base_price' => 20.00,
            'commission_rate' => 15.00,
            'is_active' => true,
            'order' => 2,
        ]);

        Service::create([
            'category_id' => $care->id,
            'name_ar' => 'مساعدة في الواجبات',
            'name_fr' => 'Aide aux devoirs',
            'description_ar' => 'مساعدة الطلاب في الواجبات المدرسية',
            'description_fr' => 'Aide scolaire à domicile',
            'icon' => '📚',
            'unit' => 'hour',
            'base_price' => 30.00,
            'commission_rate' => 15.00,
            'is_active' => true,
            'order' => 3,
        ]);

        // Transport services
        Service::create([
            'category_id' => $transport->id,
            'name_ar' => 'نقل الأثاث',
            'name_fr' => 'Déménagement',
            'description_ar' => 'خدمات نقل الأثاث والعفش',
            'description_fr' => 'Services de déménagement',
            'icon' => '📦',
            'unit' => 'task',
            'base_price' => 150.00,
            'commission_rate' => 18.00,
            'is_active' => true,
            'order' => 1,
        ]);

        Service::create([
            'category_id' => $transport->id,
            'name_ar' => 'نقل البضائع',
            'name_fr' => 'Transport de marchandises',
            'description_ar' => 'نقل البضائع والطرود',
            'description_fr' => 'Transport de colis et marchandises',
            'icon' => '🚚',
            'unit' => 'task',
            'base_price' => 50.00,
            'commission_rate' => 18.00,
            'is_active' => true,
            'order' => 2,
        ]);

        // Beauty services
        Service::create([
            'category_id' => $beauty->id,
            'name_ar' => 'تصفيف الشعر',
            'name_fr' => 'Coiffure',
            'description_ar' => 'خدمات تصفيف وقص الشعر في المنزل',
            'description_fr' => 'Services de coiffure à domicile',
            'icon' => '💇',
            'unit' => 'task',
            'base_price' => 40.00,
            'commission_rate' => 20.00,
            'is_active' => true,
            'order' => 1,
        ]);

        Service::create([
            'category_id' => $beauty->id,
            'name_ar' => 'تجميل الأظافر',
            'name_fr' => 'Manucure/Pédicure',
            'description_ar' => 'العناية بالأظافر',
            'description_fr' => 'Soins des ongles',
            'icon' => '💅',
            'unit' => 'task',
            'base_price' => 30.00,
            'commission_rate' => 20.00,
            'is_active' => true,
            'order' => 2,
        ]);

        $this->command->info('Services seeded successfully!');
    }
}
