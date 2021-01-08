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
            $table->timestamps();
            $table->string('title',400);
            $table->text('body');
            $table->integer('type')->comment('Broadcast type can be notice, result, certificate.');
            $table->integer('broadcast_group')->comment('Institution based, round based, assessment-based');
            $table->integer('broadcast_to')->comment('Institution id/ round-id/assessment-id');
            $table->unsignedBigInteger('broadcasted_by')->unsigned();
            $table->foreign('broadcasted_by')->references('id')->on('users');
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
