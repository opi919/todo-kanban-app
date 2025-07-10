<?php

namespace Database\Seeders;

use App\Models\Task;
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
            'name' => 'Dr. Md. Saiful Islam',
            'email' => 'msiscse@ru.ac.bd',
            'password' => bcrypt('124562'),
            'role' => UserRole::Admin,
        ]);

        User::factory()->create([
            'name' => 'Md Abdul Hakim',
            'email' => 'hakim@ru.ac.bd',
            'password' => bcrypt('569603'),
            'role' => UserRole::User,
        ]);

        User::factory()->create([
            'name' => 'Md. Abdullah Farook',
            'email' => 'farook@ru.ac.bd',
            'password' => bcrypt('761456'),
            'role' => UserRole::User,
        ]);
        User::factory()->create([
            'name' => 'Rahel Mahfooz Sarker',
            'email' => 'rahel@ru.ac.bd',
            'password' => bcrypt('472632'),
            'role' => UserRole::User,
        ]);
        User::factory()->create([
            'name' => 'Md. Atiqur Rahman',
            'email' => 'atiqur@ru.ac.bd',
            'password' => bcrypt('183277'),
            'role' => UserRole::User,
        ]);
        User::factory()->create([
            'name' => 'Md. Sohag Hosen',
            'email' => 'sohag@tu.ac.bd',
            'password' => bcrypt('499080'),
            'role' => UserRole::User,
        ]);
        User::factory()->create([
            'name' => 'Mst. Tazmultani Sultana',
            'email' => 'mita@ru.ac.bd',
            'password' => bcrypt('830586'),
            'role' => UserRole::User,
        ]);
        User::factory()->create([
            'name' => 'Momen Khandoker Ope',
            'email' => 'khandokermomen919@ru.ac.bd',
            'password' => bcrypt('381699'),
            'role' => UserRole::User,
        ]);
        User::factory()->create([
            'name' => 'Faizul Islam Faruq',
            'email' => 'ict_center@ru.ac.bd',
            'password' => bcrypt('557903'),
            'role' => UserRole::User,
        ]);
    }
}
