<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        User::create(['name' => 'Admin', 'email' => 'josselin@skribblio.com', 'password' => bcrypt('josselin')]);
        User::factory(5)->create();
    }
}
