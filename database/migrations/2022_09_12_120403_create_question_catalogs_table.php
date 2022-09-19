<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionCatalogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('question_catalogs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 255);
            $table->string('type', 100)->comment('educational, job preparation, institutional')->nullable();
            $table->text('institute')->nullable();
            $table->bigInteger('institute_id')->nullable();
            $table->integer('total_question')->default(0);
            $table->tinyInteger('privacy')->default(0)->comment('0 for public, 1 for private, 2 for protected');
            $table->tinyInteger('status')->default(0)->comment('available or not 0/1, whether the Question Catalog is published or not, 0 for unpublished where 1 for published');
            $table->integer('approved_by')->comment('user profile id')->nullable();
            $table->unsignedBigInteger('created_by')->unsigned()->comment('User id')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->unsigned()->comment('user id')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
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
        Schema::dropIfExists('question_catalogs');
    }
}
