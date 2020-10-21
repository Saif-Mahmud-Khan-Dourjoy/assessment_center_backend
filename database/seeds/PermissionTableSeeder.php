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
            //'permission-list',
            //'permission-create',
            //'permission-edit',
            //'permission-delete',
            'role-list',
            'role-create',
            'role-edit',
            'role-delete',
            'role-setup',
            'user-list',
            'user-create',
            'user-edit',
            'user-delete',
            'user-academic-history',
            'user-employment-history',
            'contributor-list',
            'contributor-create',
            'contributor-edit',
            'contributor-delete',
            'question-category-list',
            'question-category-create',
            'question-category-edit',
            'question-category-delete',
            'question-list',
            'question-create',
            'question-edit',
            'question-delete',
            'question-set-list',
            'question-set-create',
            'question-set-edit',
            'question-set-delete'
        ];


        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }
}
