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
        Schema::table('staff_users', function (Blueprint $table) {
            $table->string('full_name')->after('id')->nullable();
        });

        Schema::table('tour_guides', function (Blueprint $table) {
            $table->string('full_name')->after('id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('staff_users', function (Blueprint $table) {
            $table->dropColumn('full_name');
        });

        Schema::table('tour_guides', function (Blueprint $table) {
            $table->dropColumn('full_name');
        });
    }
};
