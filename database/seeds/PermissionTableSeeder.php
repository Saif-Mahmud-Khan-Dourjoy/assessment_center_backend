<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;


class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'super-admin',
            'student-only',
            'permission-list',
            //'permission-create',
            //'permission-edit',
            //'permission-delete',
            'role-delete',
            'role-create',
            'role-edit',           
            'role-list',

            'role-setup',
           
 	    'user-delete',
            'user-create',
            'user-edit',
            'user-list',
            //'user-academic-history',
            //'user-employment-history',
            
            'institute-delete',
            'institute-create',
            'institute-edit',
            'institute-list',
            

	    'round-delete',
            'round-create',
            'round-edit',
            'round-list',
            

            'contributor-delete',
            'contributor-create',
            'contributor-edit',
            'contributor-list',
            'student-delete',
            'student-create',
            'student-edit',
            'student-list',


            'question-category-delete',
            'question-category-create',
            'question-category-edit',
            'question-category-list',
                        'question-delete',

            'question-create',
            'question-edit',
            'question-list',
            'question-set-delete',
            'question-set-create',
            'question-set-edit',
            'question-set-list',

            //'question-set-answer-delete',
            'question-set-answer-create',
            //'question-set-answer-edit
            'question-set-answer-list',


        ];


        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }
}
