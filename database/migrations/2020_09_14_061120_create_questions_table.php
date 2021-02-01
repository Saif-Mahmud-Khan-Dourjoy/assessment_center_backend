<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('profile_id')->unsigned();
            $table->foreign('profile_id')->references('id')->on('user_profiles');
            $table->unsignedBigInteger('category_id')->unsigned();
            $table->foreign('category_id')->references('id')->on('question_categories');
            $table->bigInteger('institute_id')->nullable();
            $table->tinyInteger('privacy')->default(0)->comment('0 for public, 1 for private, 2 for protected');
            $table->tinyInteger('publish_status')->comment('0 for unpublished and 1 published');
            $table->char('question_type', 100)->comment('multiple choice, checkbox, short answer');
            $table->text('question_text');
            $table->text('description')->nullable();
            $table->char('option_type', 20)->comment('text, textarea, checkbox, radio');
            $table->integer('no_of_option');
            $table->integer('no_of_answer');
            $table->integer('no_of_used');
            $table->integer('no_of_comments');
            $table->integer('average_rating');
            $table->text('img')->nullable();
            $table->tinyInteger('active')->comment('0 for inactive and 1 for active');
            $table->unsignedBigInteger('created_by')->unsigned()->comment('User Id from users table');
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->unsigned()->comment('User Id from users table');
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
        Schema::dropIfExists('questions');
    }
}
