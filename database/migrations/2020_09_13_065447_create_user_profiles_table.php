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
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('institute_id')->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('email', 100);
            $table->string('phone', 50)->nullable();
            $table->date('birth_date');
            $table->string('skype', 100)->nullable();
            $table->string('profession', 100)->nullable();
            $table->string('skill', 200)->nullable();
            $table->text('about')->nullable();
            $table->string('img', 200)->nullable();
            $table->text('address')->nullable();
            $table->integer('zipcode')->nullable();
            $table->string('country', 100)->nullable();
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
