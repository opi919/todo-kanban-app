<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizations = [
            [
                'name' => 'ICT Center, RU',
                'slug' => 'ictcenter',
                'description' => 'Leading technology solutions provider specializing in software development, cloud services, and digital transformation for enterprise clients.',
                'is_active' => true,
                'settings' => [
                    'timezone' => 'UTC',
                    'currency' => 'BDT',
                    'working_hours' => '09:00-17:00',
                    'working_days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'],
                ],
            ],
        ];

        foreach ($organizations as $orgData) {
            Organization::create($orgData);
            $this->command->info("Created organization: {$orgData['name']}");
        }
    }
}
