<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('staff_supervisor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_user_id');
            $table->unsignedBigInteger('supervisor_id');
            $table->timestamps();

            $table->foreign('staff_user_id')->references('id')->on('staff_users')->onDelete('cascade');
            $table->foreign('supervisor_id')->references('id')->on('users')->onDelete('cascade');
            
            // Prevent duplicate assignments
            $table->unique(['staff_user_id', 'supervisor_id']);
        });

        // Migrate existing data
        Schema::table('staff_users', function (Blueprint $table) {
            // Copy existing supervisor relationships
            DB::statement('INSERT INTO staff_supervisor (staff_user_id, supervisor_id, created_at, updated_at) 
                          SELECT id, supervisor, NOW(), NOW() 
                          FROM staff_users 
                          WHERE supervisor IS NOT NULL');
            
            // Remove old column
            $table->dropColumn('supervisor');
        });
    }

    public function down()
    {
        Schema::table('staff_users', function (Blueprint $table) {
            $table->unsignedBigInteger('supervisor')->nullable();
        });

        // Restore the first supervisor relationship for each staff
        DB::statement('UPDATE staff_users su 
                      SET supervisor = (
                          SELECT supervisor_id 
                          FROM staff_supervisor ss 
                          WHERE ss.staff_user_id = su.id 
                          LIMIT 1
                      )');

        Schema::dropIfExists('staff_supervisor');
    }
};