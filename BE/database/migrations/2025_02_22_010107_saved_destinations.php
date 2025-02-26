<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Saved destinations table
        Schema::create('saved_destinations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('business_user_id');

            // Foreign Keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop Foreign Keys
        Schema::table('saved_destinations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['business_user_id']);
        });
        
        // Drop table
        Schema::dropIfExists('saved_destinations');
    }
};
