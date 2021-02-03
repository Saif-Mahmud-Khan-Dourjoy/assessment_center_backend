<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBroadcastsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title',400)->comment('Title of the broadcast message');
            $table->text('body');
            $table->integer('type')->dfault(0)->comment('Type of broadcast, 0 for notice');
            $table->integer('group')->default(0)->comment('To which group of students want to send, 0 for everyone under institute');
            $table->unsignedBigInteger('broadcast_to')->comment('According to the group it  might be institute id, round-id, question-set-id');
            $table->unsignedBigInteger('broadcast_by')->unsigned();
            $table->foreign('broadcast_by')->references('id')->on('users');
            $table->unsignedBigInteger('institute_id')->unsigned();
            $table->foreign('institute_id')->on('institutes')->references('id')->onDelete('cascade');
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
        Schema::dropIfExists('broadcasts');
    }
}
