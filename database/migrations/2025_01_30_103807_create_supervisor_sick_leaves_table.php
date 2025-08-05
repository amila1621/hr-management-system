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
        Schema::create('supervisor_sick_leaves', function (Blueprint $table) {
            $table->id();
            $table->integer('staff_id');
            $table->string('date');
            $table->longText('supervisor_id')->nullable();
            $table->longText('supervisor_remark')->nullable();
            $table->string('image')->nullable();
            $table->longText('admin_id')->nullable();
            $table->longText('admin_remark')->nullable();
            $table->integer('status')->default(0)->comment('0=pending, 1=waiting for admin, 2=approved, 3=rejected, 4=cancelled');
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
        Schema::dropIfExists('supervisor_sick_leaves');
    }
};
