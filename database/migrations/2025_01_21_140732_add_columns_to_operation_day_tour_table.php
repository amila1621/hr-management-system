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
        Schema::table('operation_day_tour', function (Blueprint $table) {
            $table->string('day_night')->nullable()->after('remark');
            $table->boolean('is_duration_updated')->default(0)->comment('0-Not updated / 1-Updated')->after('remark');
            $table->boolean('is_edited')->default(0)->comment('0-Not Edited / 1-Edited')->after('remark');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('operation_day_tour', function (Blueprint $table) {
            $table->dropColumn('day_night');
            $table->dropColumn('is_duration_updated');
            $table->dropColumn('is_edited');
        });
    }
};
