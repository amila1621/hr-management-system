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
            $table->string('guide_image')->nullable()->after('guide_comment');
        });
    }

    public function down()
    {
        Schema::table('event_salaries', function (Blueprint $table) {
            $table->dropColumn('guide_image');
        });
    }
};
