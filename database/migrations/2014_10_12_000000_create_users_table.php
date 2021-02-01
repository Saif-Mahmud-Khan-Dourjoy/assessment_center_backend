<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email');
            $table->tinyInteger('status')->nullable()->default('0')->comment('active or not 1/0');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('img',400)->nullable();
            $table->unsignedBigInteger('institute_id')->unsigned();
            $table->foreign('institute_id')->on('institutes')->references('id')->onDelete('cascade');
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
