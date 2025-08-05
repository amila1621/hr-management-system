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
        Schema::create('salary_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('staff_name');
            $table->string('staff_full_name');
            $table->string('staff_role');
            $table->string('staff_email');
            $table->string('staff_department');
            $table->date('date');
            $table->text('description');
            $table->string('work_periods');
            $table->string('work_hours')->default('00:00');
            $table->string('holiday_hours')->default('00:00');
            $table->string('evening_hours')->default('00:00');
            $table->string('evening_holiday_hours')->default('00:00');
            $table->string('night_hours')->default('00:00');
            $table->string('night_holiday_hours')->default('00:00');
            $table->string('sick_leaves')->default('00:00');
            $table->boolean('is_intern')->default(false);
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
        Schema::dropIfExists('salary_reports');
    }
};
