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
            $table->boolean('is_chore')->default(false);
            $table->boolean('is_guide_updated')->default(false);
            $table->text('guide_comment')->nullable();
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
            $table->dropColumn('is_chore');
            $table->dropColumn('is_guide_updated');
            $table->dropColumn('guide_comment');
        });
    }
};
