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
        Schema::table('receipts', function (Blueprint $table) {
            $table->string('department')->nullable()->after('user_id');
            $table->string('rejection_reason')->nullable()->after('department');
            $table->string('approval_description')->nullable()->after('department');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn('department');
            $table->dropColumn('rejection_reason');
            $table->dropColumn('approval_description');
        });
    }
};
