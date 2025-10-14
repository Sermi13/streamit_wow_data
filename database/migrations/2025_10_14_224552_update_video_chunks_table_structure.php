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
        Schema::dropIfExists('video_chunks');

        // Cria a tabela com a estrutura exata especificada
        DB::statement("
            CREATE TABLE `video_chunks` (
                `id` int NOT NULL AUTO_INCREMENT,
                `entertainment_id` int DEFAULT NULL,
                `text_content` text NOT NULL,
                `speaker` varchar(255) DEFAULT NULL,
                `embedding` json DEFAULT NULL,
                `episode_id` int DEFAULT NULL,
                `start_time_ms` int DEFAULT NULL,
                `end_time_ms` int DEFAULT NULL,
                `start_time_formatted` varchar(20) NOT NULL,
                `end_time_formatted` varchar(20) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_chunks');
    }
};
