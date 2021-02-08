<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionSetCandidatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('question_set_candidates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger("question_set_id")->unsigned();
            $table->foreign("question_set_id")->on("question_sets")->references('id')->onDelete('cascade')->comment("Question-id i.e Assessment Id");
            $table->unsignedBigInteger("profile_id")->unsigned();
            $table->foreign("profile_id")->on("user_profiles")->references("id")->onDelete('cascade')->comment("who is Illegible (participated for now) for this round");
            $table->smallInteger("attended")->unsigned()->default(0)->comment("Whether this student/candidate is participated on this exam or not!");
            $table->unique('question_set_id','profile_id');
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
        Schema::dropIfExists('question_set_candidates');
    }
}
