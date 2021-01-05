<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionSetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('question_sets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('title', 255);
            $table->char('type', 100)->comment('educational, job preparation, institutional');
            $table->text('institute')->nullable();
            $table->bigInteger('institute_id')->nullable();
            $table->float('assessment_time')->comment('in min');
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->smallInteger('each_question_time')->comment('flag for having each question have time or not');
            $table->float('total_mark');
            $table->integer('total_question')->default(0);
            $table->tinyInteger('privacy')->default(0)->comment('0 for public, 1 for private, 2 for protected');
            $table->tinyInteger('status')->comment('available or not 0/1');
            $table->integer('created_by')->comment('user profile id');
            $table->integer('approved_by')->comment('user profile id');
            $table->unsignedBigInteger('round_id')->unsigned()->comment('For tracking the assessment, in which round it will be served');
            $table->foreign('round_id')->references('id')->on('rounds')->onDelete('cascade');
            $table->smallInteger('published')->default('0')->comment('whether the Assessment is published or not, 0 for unpublished where 1 for published');
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
        Schema::dropIfExists('question_sets');
    }
}
