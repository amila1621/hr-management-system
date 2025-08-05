<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('combined_staff_guide_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('year');
            $table->integer('month');
            $table->string('total_work_hours')->default('0:00');
            $table->string('total_holiday_hours')->default('0:00');
            $table->string('total_night_hours')->default('0:00');
            $table->string('total_holiday_night_hours')->default('0:00');
            $table->string('total_sick_leaves')->default('0:00');
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            // Indexes
            $table->unique(['user_id', 'year', 'month']);
            $table->index(['year', 'month']);
            
            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combined_staff_guide_hours');
    }
};