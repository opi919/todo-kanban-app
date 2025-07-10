<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\UserRole;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Super Admin (not tied to any organization)
        $superAdmin = User::create([
            'name' => 'System Administrator',
            'email' => 'super@admin.com',
            'password' => Hash::make('admin123'),
            'role' => UserRole::Admin,
            'organization_id' => null,
            'admin_id' => null,
            'permissions' => [
                'manage_organizations' => true,
                'manage_all_users' => true,
                'view_all_tasks' => true,
                'system_settings' => true,
            ],
        ]);

        $this->command->info("Created Super Admin: {$superAdmin->email}");

        // Get all organizations
        $organizations = Organization::all();

        foreach ($organizations as $organization) {
            $this->createOrganizationUsers($organization);
        }
    }

    private function createOrganizationUsers(Organization $organization): void
    {

        $admin = User::create([
            'name' => 'Dr. Md. Saiful Islam',
            'email' => 'msiscse@ru.ac.bd',
            'password' => Hash::make('124562'),
            'role' => UserRole::Admin,
            'organization_id' => $organization->id,
            'admin_id' => null,
            'permissions' => [
                'manage_organization_users' => true,
                'manage_organization_tasks' => true,
                'view_organization_reports' => true,
                'assign_tasks' => true,
            ],
        ]);

        $this->command->info("Created Organization Admin: {$admin->email} for {$organization->name}");

        // Define user templates for different organizations
        $userTemplates = [
            [
                'name' => 'Md Abdul Hakim',
                'email' => 'hakim@ru.ac.bd',
                'password' => Hash::make('569603'),
            ],
            [
                'name' => 'Md Abdullah Farook',
                'email' => 'farook@ru.ac.bd',
                'password' => Hash::make('761456'),
            ],
            [
                'name' => 'Rahel Mahfooz Sarker',
                'email' => 'rahel@ru.ac.bd',
                'password' => Hash::make('472632'),
            ],
            [
                'name' => 'Md. Atiqur Rahman',
                'email' => 'atiqur@ru.ac.bd',
                'password' => Hash::make('183277'),
            ],
            [
                'name' => 'Md. Sohag Hosen',
                'email' => 'sohag@ru.ac.bd',
                'password' => Hash::make('499080'),
            ],
            [
                'name' => 'Mst. Tazmultani Sultana',
                'email' => 'mita@ru.ac.bd',
                'password' => Hash::make('830586'),
            ],
            [
                'name' => 'Momen Khandoker Ope',
                'email' => 'khandokermomen919@ru.ac.bd',
                'password' => Hash::make('381699'),
            ],
            [
                'name' => 'Faizul Islam Faruq',
                'email' => 'ict_center@ru.ac.bd',
                'password' => Hash::make('557903'),
            ],
        ];

        foreach ($userTemplates as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'role' => UserRole::User,
                'organization_id' => $organization->id,
                'admin_id' => $admin->id,
                'permissions' => [
                    'view_assigned_tasks' => true,
                    'update_task_status' => true,
                    'comment_on_tasks' => true,
                ],
            ]);

            $this->command->info("Created User: {$user->email} under {$organization->name}");
        }
    }
}
