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
    Schema::create('staff_monthly_hours', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('staff_id');
        $table->date('date');
        $table->json('hours_data');
        $table->timestamps();

        $table->foreign('staff_id')->references('id')->on('staff_users')->onDelete('cascade');
        $table->unique(['staff_id', 'date']);
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('staff_monthly_hours');
    }
};
