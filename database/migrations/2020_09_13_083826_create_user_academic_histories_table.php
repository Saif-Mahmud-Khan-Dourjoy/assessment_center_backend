<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAcademicHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_academic_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('profile_id')->unsigned();
            $table->foreign('profile_id')->references('id')->on('user_profiles');
            $table->char('exam_course_title', 250);
            $table->text('major')->default('general')->comment('Default field is general, but it could be Science, Arts, Business, Madrasha, Diploma etc');
            $table->char('institute', 200);
            $table->float('result')->nullable();
            $table->char('start_year', 50)->default(date('Y-m-d'));
            $table->char('end_year', 50)->nullable();
            $table->enum('currently_study',['0', '1'])->default('1')->comment('0 for No, 1 for yes');
            $table->char('duration', 200)->nullable();
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
        Schema::dropIfExists('user_academic_histories');
    }
}
