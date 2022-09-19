<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionCatalogDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('question_catalog_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('question_catalog_id')->unsigned();
            $table->foreign('question_catalog_id')->references('id')->on('question_catalogs')->onDelete('cascade');
            $table->unsignedBigInteger('question_id')->unsigned();
            $table->foreign('question_id')->references('id')->on('questions');
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
        Schema::dropIfExists('question_catalog_details');
    }
}
