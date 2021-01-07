<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->bigInteger('institute_id')->nullable();
            $table->char('first_name', 100);
            $table->char('last_name', 100)->nullable();
            $table->char('email', 100);
            $table->char('phone', 50)->nullable();
            $table->char('skype', 100)->nullable();
            $table->char('profession', 100)->nullable();
            $table->char('skill', 200)->nullable();
            $table->text('about')->nullable();
            $table->char('img', 200)->nullable();
            $table->text('address', 200)->nullable();
            $table->integer('zipcode')->nullable();
            $table->char('country', 100)->nullable();
            $table->string('guard_name')->default('web');
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
        Schema::dropIfExists('user_profiles');
    }
}
