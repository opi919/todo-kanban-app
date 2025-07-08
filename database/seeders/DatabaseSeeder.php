<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@ru.ac.bd',
            'password' => bcrypt('admin123'),
            'role' => UserRole::Admin,
        ]);

        User::factory()->create([
            'name' => 'opi',
            'email' => 'opi@ru.ac.bd',
            'password' => bcrypt('opi123'),
            'role' => UserRole::User,
        ]);

        User::factory()->create([
            'name' => 'sami',
            'email' => 'sami@ru.ac.bd',
            'password' => bcrypt('sami123'),
            'role' => UserRole::User,
        ]);
    }
}
