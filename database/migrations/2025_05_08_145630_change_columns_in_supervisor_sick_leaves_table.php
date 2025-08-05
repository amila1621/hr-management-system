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
        // Step 1: Rename the column first
        Schema::table('supervisor_sick_leaves', function (Blueprint $table) {
            $table->renameColumn('date', 'start_date');
        });

        // Step 2: Now add the new column after the renamed one
        Schema::table('supervisor_sick_leaves', function (Blueprint $table) {
            $table->date('end_date')->nullable()->after('start_date');
        });

        Schema::table('supervisor_sick_leaves', function (Blueprint $table) {
            $table->text('description')->nullable()->after('end_date');
            $table->text('department')->nullable()->after('description');
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Step 1: Remove the added column first
        Schema::table('supervisor_sick_leaves', function (Blueprint $table) {
            $table->dropColumn('end_date');
        });

        // Step 2: Rename the column back to its original name
        Schema::table('supervisor_sick_leaves', function (Blueprint $table) {
            $table->renameColumn('start_date', 'date');
        });

        Schema::table('supervisor_sick_leaves', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->dropColumn('department');
        });
    }
};
