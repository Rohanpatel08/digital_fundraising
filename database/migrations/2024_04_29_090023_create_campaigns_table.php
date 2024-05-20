<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('account_plan_id')->constrained()->onDelete('cascade');
            $table->bigInteger('unique_code');
            $table->string('campaign_name');
            $table->longText('description');
            $table->string('campaign_url')->nullable();
            $table->string('banner_image')->nullable();
            $table->json('images')->nullable();
            $table->timestamps();
            $table->softDeletes();
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