<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            // Adiciona coluna para armazenar embedding do ai_summary
            $table->json('ai_summary_embedding')
                ->nullable()
                ->comment('Embedding (1536D) do campo ai_summary para busca vetorial de episódios')
                ->after('ai_summary');
        });

        // Adiciona índice funcional usando raw SQL (não suportado nativamente pelo Blueprint)
        DB::statement('ALTER TABLE episodes ADD KEY `idx_episodes_has_embedding` ((CASE WHEN (`ai_summary_embedding` IS NOT NULL) THEN 1 ELSE 0 END))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            // Remove o índice funcional
            $table->dropIndex('idx_episodes_has_embedding');

            // Remove a coluna
            $table->dropColumn('ai_summary_embedding');
        });
    }
};
