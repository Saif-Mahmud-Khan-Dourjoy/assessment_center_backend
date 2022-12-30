<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRecruiterRoleIdToRoleSetupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('role_setups', function (Blueprint $table) {
            $table->unsignedBigInteger('recruiter_role_id')->unsigned();
            $table->foreign('recruiter_role_id')->references('id')->on('roles');
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
            $table->dropColumn('recruiter_role_id');
        });
    }
}
