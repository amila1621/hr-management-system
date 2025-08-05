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
        Schema::create('staff_midnight_phone', function (Blueprint $table) {
            $table->id();
            $table->integer('staff_id');
            $table->string('staff_name');
            $table->string('reason');
            $table->date('date');
            $table->datetime('start_time');
            $table->datetime('end_time');
            $table->date('applied_date');
            $table->integer('created_by');
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
        Schema::dropIfExists('staff_midnight_phone');
    }
};
