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
        Schema::create('faiss_indexes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('entertainment_id')->unsigned()->nullable();
            $table->integer('episode_id')->unsigned()->nullable();
            $table->longText('index_data')->comment('Dados binários do índice FAISS');
            $table->json('index_metadata')->comment('Metadados do índice FAISS');
            $table->string('created_at', 50)->nullable();

            // Índices
            $table->index('entertainment_id', 'ix_faiss_indexes_entertainment_id');
            $table->index('episode_id', 'ix_faiss_indexes_episode_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faiss_indexes');
    }
};
