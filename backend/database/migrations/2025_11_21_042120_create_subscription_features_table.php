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
        Schema::create('subscription_features', function (Blueprint $table) {
            $table->id();
            $table->enum('plan', ['basic', 'pro', 'enterprise']);
            $table->string('feature_key');
            $table->string('feature_name');
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->integer('limit_value')->nullable(); // For features with limits (e.g., max agents)
            $table->timestamps();

            $table->unique(['plan', 'feature_key']);
            $table->index('plan');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_features');
    }
};
