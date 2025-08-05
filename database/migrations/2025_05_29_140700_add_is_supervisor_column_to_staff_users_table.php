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
            $table->boolean('is_supervisor')->default(false)->after('user_id')->comment('Indicates if the user is a supervisor');
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
            $table->dropColumn('is_supervisor');
        });
    }
};
