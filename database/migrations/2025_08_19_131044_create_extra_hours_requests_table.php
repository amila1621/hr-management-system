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
        Schema::create('extra_hours_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guide_id');
            $table->unsignedBigInteger('event_id');
            $table->text('explanation');
            $table->datetime('requested_end_time');
            $table->datetime('original_end_time');
            $table->integer('extra_hours_minutes');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_comment')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('guide_id')->references('id')->on('tour_guides');
            $table->foreign('event_id')->references('id')->on('events');
            $table->foreign('approved_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('extra_hours_requests');
    }
};
