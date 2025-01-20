<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('salary_time_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('guide_id');
            $table->unsignedBigInteger('adjusted_by'); // user who made the adjustment
            $table->datetime('original_end_time');
            $table->string('added_time'); // HH:mm format
            $table->datetime('new_end_time');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events');
            $table->foreign('guide_id')->references('id')->on('users');
            $table->foreign('adjusted_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('salary_time_adjustments');
    }
};