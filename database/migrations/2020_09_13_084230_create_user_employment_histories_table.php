<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserEmploymentHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_employment_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('profile_id')->unsigned();
            $table->foreign('profile_id')->references('id')->on('user_profiles');
            $table->char('institute', 250);
            $table->char('position', 200);
            $table->text('responsibility');
            $table->char('start_date', 50);
            $table->char('end_date', 50)->nullable();
            $table->char('duration', 250)->nullable();
            $table->enum('currently_work',['0', '1'])->comment('0 for No, 1 for yes');
            $table->text('description')->nullable();
            $table->char('check_status', 100)->nullable();
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
        Schema::dropIfExists('user_employment_histories');
    }
}
