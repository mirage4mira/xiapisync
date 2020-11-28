<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use App\User;
use App\Models\RoleHierarchy;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $numberOfUsers = 2;
        $usersIds = array();

        $faker = Faker::create();
       

        /*  insert users   */
        $user = User::create([ 
            'name' => 'admin',
            'email' => 'admin@admin.com',
            'email_verified_at' => now(),
            'password' => bcrypt(123), // password
            'remember_token' => Str::random(10),
        ]);


        for($i = 0; $i<$numberOfUsers; $i++){
            $user = User::create([ 
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'email_verified_at' => now(),
                'password' => bcrypt(123), // password
                'remember_token' => Str::random(10),
            ]);

            array_push($usersIds, $user->id);
        }
    }
}