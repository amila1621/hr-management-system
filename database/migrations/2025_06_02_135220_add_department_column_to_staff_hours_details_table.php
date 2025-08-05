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
        Schema::table('staff_hours_details', function (Blueprint $table) {
            $table->string('department')->nullable()->after('midnight_phone')->comment('Department of the staff member');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('staff_hours_details', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }
};
