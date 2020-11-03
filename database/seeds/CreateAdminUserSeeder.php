<?php

use Illuminate\Database\Seeder;
use App\User;
use App\UserProfile;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\RoleSetup;

class CreateAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@nsl.com',
            'password' => bcrypt('123456789')
        ]);

        UserProfile::create([
            'user_id' => $user['id'],
            'first_name' => 'Admin',
            'email' => 'admin@nsl.com',
        ]);

        $role = Role::create(['name' => 'Admin']);

        $permissions = Permission::pluck('id','id')->all();

        $role->syncPermissions($permissions);

        $user->assignRole([$role->id]);

        RoleSetup::create([
            'contributor_role_id' => 1,
            'student_role_id' => 1,
            'new_register_user_role_id' => 1,
        ]);
    }
}
