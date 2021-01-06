<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rounds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->string('name', 200);
            $table->unsignedBigInteger('institute_id')->unsigned();
            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade')->nullable();
            $table->string('passing_criteria')->default('sort');                            // alternate value could be 'pass'
            $table->integer('number')->default('10');                                       // if passing criteria is pass then number will be pass mark otherwise it will sort top 10
            $table->integer('status')->default('1')->comment('Active:1, Inactive:0');
            $table->unsignedBigInteger('created_by')->unsigned();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->unsigned();
            $table->foreign('updated_by')->references('id')->on('users')->nullable();
            $table->unique(['name','institute_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rounds');
    }
}
