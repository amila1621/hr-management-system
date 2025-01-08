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
        Schema::table('event_salaries', function (Blueprint $table) {
            $table->timestamp('guide_start_time')->nullable()->after('guide_comment'); // Storing guide's start time
            $table->timestamp('guide_end_time')->nullable()->after('guide_start_time'); // Storing guide's end time
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('event_salaries', function (Blueprint $table) {
            $table->dropColumn('guide_start_time');
            $table->dropColumn('guide_end_time');
        });
    }
};
