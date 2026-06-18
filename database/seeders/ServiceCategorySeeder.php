<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Seed development/test service categories for initial service setup.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Massage Therapy',
                'description' => 'Massage services for relaxation, recovery, and wellness.',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Body Treatments',
                'description' => 'Body scrub and body care services.',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Wellness Packages',
                'description' => 'Bundled spa services for longer wellness sessions.',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            ServiceCategory::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
