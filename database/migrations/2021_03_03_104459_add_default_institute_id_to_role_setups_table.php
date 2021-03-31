<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultInstituteIdToRoleSetupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('role_setups', function (Blueprint $table) {
            $table->unsignedBigInteger("default_institute_id")->unsigned()->default(1)->after('new_register_user_role_id');
            $table->foreign("default_institute_id")->on("institutes")->references("id")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('role_setups', function (Blueprint $table) {
            //
        });
    }
}
