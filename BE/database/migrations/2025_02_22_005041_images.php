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
        // Images table
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_profile_id');
            $table->text('path_name');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('business_profile_id')->references('id')->on('business_profiles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop Foreign Keys
        Schema::table('images', function (Blueprint $table) {
            $table->dropForeign(['business_profile_id']);
        });
        
        // Drop Table
        Schema::dropIfExists('images');
    }
};
