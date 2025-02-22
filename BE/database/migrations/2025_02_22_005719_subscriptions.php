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
        // Subscriptions table
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_user_id');
            $table->enum('type', ['none', 'monthly', 'yearly']);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('active')->default(false);

            // Foreign Keys
            $table->foreign('business_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop Foreign Keys
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['business_user_id']);
        });
        
        // Drop table
        Schema::dropIfExists('subscriptions');
    }
};
