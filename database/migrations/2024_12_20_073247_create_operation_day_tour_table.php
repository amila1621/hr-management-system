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
        Schema::create('operation_day_tour', function (Blueprint $table) {
            $table->id();
            $table->string('event_id');
            $table->date('tour_date');
            $table->string('duration')->nullable();
            $table->string('tour_name')->nullable();
            $table->string('vehicle')->nullable();
            $table->string('pickup_time')->nullable();
            $table->string('pickup_location')->nullable();
            $table->integer('pax')->nullable();
            $table->string('guide')->nullable();
            $table->string('available')->nullable();
            $table->text('remark')->nullable();
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
        Schema::dropIfExists('operation_day_tour');
    }
};
