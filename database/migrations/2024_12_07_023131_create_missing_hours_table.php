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
        Schema::create('missing_hours', function (Blueprint $table) {
            $table->id();
            $table->integer('guide_id');
            $table->string('guide_name');
            $table->string('tour_name');
            $table->date('date');
            $table->string('normal_hours')->default('0');
            $table->string('normal_night_hours')->default('0');
            $table->string('holiday_hours')->default('0');
            $table->string('holiday_night_hours')->default('0');
            $table->date('applied_at');
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
        Schema::dropIfExists('missing_hours');
    }
};
