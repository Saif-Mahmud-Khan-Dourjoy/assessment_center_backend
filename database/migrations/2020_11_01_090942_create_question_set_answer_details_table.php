<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionSetAnswerDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('question_set_answer_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('question_set_answer_id')->unsigned();
            $table->foreign('question_set_answer_id')->references('id')->on('question_set_answers');
            $table->unsignedBigInteger('question_id')->unsigned();
            $table->foreign('question_id')->references('id')->on('questions');
            $table->char('answer', 100);
            $table->float('mark');
            
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
        Schema::dropIfExists('question_set_answer_details');
    }
}
