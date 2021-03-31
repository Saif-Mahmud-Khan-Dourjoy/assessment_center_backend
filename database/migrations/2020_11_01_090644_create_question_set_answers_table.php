<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionSetAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('question_set_answers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('question_set_id')->unsigned();
            $table->foreign('question_set_id')->references('id')->on('question_sets')->onDelete('cascade');
            $table->unsignedBigInteger('profile_id');
            $table->foreign('profile_id')->references('id')->on('user_profiles');
            $table->float('time_taken');
            $table->float('total_mark');
            $table->smallInteger('served')->nullable();
//            $table->unique('question_set_id','profile_id');
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
        Schema::dropIfExists('question_set_answers');
    }
}
