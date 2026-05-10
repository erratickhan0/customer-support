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
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('ai_provider')->default('openai')->after('is_active');
            $table->float('ai_confidence_threshold')->default(0.50)->after('ai_provider');
            $table->boolean('ai_auto_handoff')->default(true)->after('ai_confidence_threshold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn([
                'ai_provider',
                'ai_confidence_threshold',
                'ai_auto_handoff',
            ]);
        });
    }
};
