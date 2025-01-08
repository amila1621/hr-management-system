<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_salaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('eventId');
            $table->unsignedBigInteger('guideId');
            $table->foreign('eventId')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('guideId')->references('id')->on('tour_guides')->onDelete('cascade');
            $table->double('totalSalary');
            $table->double('normal_hours');
            $table->double('sunday_hours');
            $table->double('holiday_hours');
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
        Schema::dropIfExists('event_salaries');
    }
};
