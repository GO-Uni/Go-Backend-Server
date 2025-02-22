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
        // User activities table
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('business_user_id');
            $table->string('category')->nullable();
            $table->unsignedBigInteger('activity_type_id');
            $table->float('activity_value')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Foreign Keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('activity_type_id')->references('id')->on('activity_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop Foreign Keys
        Schema::table('user_activities', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['business_user_id']);
            $table->dropForeign(['activity_type_id']);
        });
        
        // Drop table
        Schema::dropIfExists('user_activities');
    }
};
