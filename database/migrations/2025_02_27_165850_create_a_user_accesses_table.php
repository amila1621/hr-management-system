<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('access_type', 100); // Added length limit
            $table->boolean('has_access')->default(false);
            $table->timestamps();
            
            // Unique constraint to prevent duplicate entries
            $table->unique(['user_id', 'access_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_accesses');
    }

};
