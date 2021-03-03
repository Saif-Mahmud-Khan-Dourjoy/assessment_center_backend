<?php
use \App\Institute;
use App\Contributor;
use App\Student;
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
        //Institution addition
        Institute::create([
            'name'=>'Neural Semiconductor',
            'email'=>'nsl@nsl.com',
            'contact_no'=>'01233276827',
        ]);
        // Add user credential
        $user = User::create([
            'name' => 'Admin',
            'username'=>'Admin',
            'email' => 'admin@nsl.com',
            'password' => bcrypt('123456789'),
            'status' => '1',
            'institute_id'=>1,

        ]);

        // Add User Profile
        $user_profile = UserProfile::create([
            'user_id' => $user['id'],
            'institute_id'=>'1',
            'first_name' => 'Admin',
            'last_name' => 'Admin',
            'email' => 'admin@nsl.com',
            'birth_date'=>'1990-01-10'
        ]);

        // Add Contributor Info
        $contributor_data = [
            'profile_id' => $user_profile['id'],
            'completing_percentage' => 100,
            'total_question' => 0,
            'average_rating' => 0,
            'approve_status' => 0,
            'active_status' => 0,
            'guard_name' => 'web',
        ];
        $contributor = Contributor::create( $contributor_data );

        // Add Student Info
        $student_data = [
            'profile_id' => $user_profile['id'],
            'completing_percentage' => 100,
            'total_complete_assessment' => 0,
            'approve_status' => 0,
            'active_status' => 0,
            'guard_name' => 'web',
        ];
        $student = Student::create( $student_data );

        $role = Role::create(['name' => 'Admin']);

        $permissions = Permission::pluck('id','id')->all();

        $role->syncPermissions($permissions);

        $user->assignRole([$role->id]);

        $student_role = Role::create(['name'=>'Student', 'guard_name'=>'web']);
        $student_permissions= ['student-only', 'institute-list','round-list','question-category-list','user-list','user-edit','question-list','question-set-list','question-set-answer-create','question-set-answer-list'];
        $student_permissions= Permission::whereIn('name',$student_permissions)->get();
        $student_role->syncPermissions($student_permissions);

        RoleSetup::create([
            'contributor_role_id' => 1,
            'student_role_id' => $student_role->id,
            'new_register_user_role_id' => 1,
            'default_institute_id'=>1,
        ]);
    }
}
