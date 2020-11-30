<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionSetDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('question_set_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('question_set_id')->unsigned();
            $table->foreign('question_set_id')->references('id')->on('question_sets');
            $table->unsignedBigInteger('question_id')->unsigned();
            $table->foreign('question_id')->references('id')->on('questions');
            $table->float('mark');
            $table->tinyInteger('partial_marking_status')->comment('1 for true, 0 for false');
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
        Schema::dropIfExists('question_set_details');
    }
}
