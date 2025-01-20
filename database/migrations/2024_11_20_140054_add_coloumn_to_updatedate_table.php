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
        Schema::table('updatedate', function (Blueprint $table) {
            $table->date('until_date_pending_approvals')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('updatedate', function (Blueprint $table) {
            $table->dropColumn('until_date_pending_approvals');
        });
    }
};
