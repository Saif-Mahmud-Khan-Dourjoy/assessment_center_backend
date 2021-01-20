<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoleSetupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_setups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('contributor_role_id')->unsigned();
            $table->foreign('contributor_role_id')->references('id')->on('roles');
            $table->unsignedBigInteger('student_role_id')->unsigned();
            $table->foreign('student_role_id')->references('id')->on('roles');
            $table->unsignedBigInteger('new_register_user_role_id')->unsigned();
            $table->foreign('new_register_user_role_id')->references('id')->on('roles');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_setups');
    }
}
