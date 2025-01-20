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
        Schema::create('sick_leaves', function (Blueprint $table) {
            $table->id();
            $table->integer('guide_id');
            $table->string('guide_name');
            $table->string('tour_name');
            $table->date('date');
            $table->datetime('start_time')->nullable();
            $table->datetime('end_time')->nullable();
            $table->string('normal_hours', 20)->default('0');
            $table->string('normal_night_hours', 20)->default('0');
            $table->string('holiday_hours', 20)->default('0');
            $table->string('holiday_night_hours', 20)->default('0');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sick_leaves');
    }
};
