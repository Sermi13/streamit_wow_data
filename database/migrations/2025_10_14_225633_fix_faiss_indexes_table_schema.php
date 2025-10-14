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
        // Dropa a tabela se existir para recriar com a estrutura correta
        Schema::dropIfExists('faiss_indexes');

        // Cria a tabela com a estrutura exata especificada
        DB::statement("
            CREATE TABLE `faiss_indexes` (
                `id` int NOT NULL AUTO_INCREMENT,
                `entertainment_id` int DEFAULT NULL,
                `episode_id` int DEFAULT NULL,
                `index_data` longblob NOT NULL,
                `index_metadata` json NOT NULL,
                `created_at` varchar(50) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `ix_faiss_indexes_entertainment_id` (`entertainment_id`),
                KEY `ix_faiss_indexes_episode_id` (`episode_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faiss_indexes');
    }
};
