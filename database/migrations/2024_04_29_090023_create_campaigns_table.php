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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id()->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_plan_id')->constrained()->onDelete('cascade');
            $table->bigInteger('unique_code');
            $table->string('campaign_name');
            $table->longText('description');
            $table->json('banner_image')->nullable();
            $table->json('images')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
