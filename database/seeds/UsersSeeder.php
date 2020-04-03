<?php

use Illuminate\Database\Seeder;
use App\Models\User;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'josselin@skribblio.com',
            'password' => bcrypt('josselin'),
        ]);

        factory(User::class, 5)->create();
    }
}
