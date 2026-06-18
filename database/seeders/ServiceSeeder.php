<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Seed development/test services. Prices are sample placeholders only.
     */
    public function run(): void
    {
        $massageCategoryId = ServiceCategory::where('name', 'Massage Therapy')->value('id');
        $bodyTreatmentCategoryId = ServiceCategory::where('name', 'Body Treatments')->value('id');
        $packageCategoryId = ServiceCategory::where('name', 'Wellness Packages')->value('id');

        $services = [
            [
                'service_category_id' => $massageCategoryId,
                'name' => 'Swedish Massage',
                'description' => 'Classic relaxation massage sample service.',
                'duration_minutes' => 60,
                'price' => 600,
                'status' => 'active',
            ],
            [
                'service_category_id' => $massageCategoryId,
                'name' => 'Deep Tissue Massage',
                'description' => 'Focused pressure massage sample service.',
                'duration_minutes' => 60,
                'price' => 800,
                'status' => 'active',
            ],
            [
                'service_category_id' => $massageCategoryId,
                'name' => 'Ventosa Massage',
                'description' => 'Cupping-assisted massage sample service.',
                'duration_minutes' => 75,
                'price' => 950,
                'status' => 'active',
            ],
            [
                'service_category_id' => $bodyTreatmentCategoryId,
                'name' => 'Body Scrub',
                'description' => 'Exfoliating body treatment sample service.',
                'duration_minutes' => 45,
                'price' => 700,
                'status' => 'active',
            ],
            [
                'service_category_id' => $packageCategoryId,
                'name' => 'Relaxation Package',
                'description' => 'Sample bundled service for future package workflows.',
                'duration_minutes' => 120,
                'price' => 1500,
                'status' => 'active',
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['name' => $service['name']],
                $service
            );
        }
    }
}
