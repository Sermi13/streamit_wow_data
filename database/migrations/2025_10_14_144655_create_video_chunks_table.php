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
        Schema::create('video_chunks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entertainment_id')->nullable()->index();
            $table->unsignedBigInteger('episode_id')->nullable()->index();
            $table->text('text_content');
            $table->string('speaker')->nullable();
            $table->json('embedding')->nullable();
            $table->integer('start_time_ms')->nullable();
            $table->integer('end_time_ms')->nullable();
            $table->string('start_time_formatted', 20)->nullable();
            $table->string('end_time_formatted', 20)->nullable();
            $table->timestamps();

            // Foreign keys (opcional, caso queira garantir integridade)
            // $table->foreign('entertainment_id')->references('id')->on('entertainments')->onDelete('cascade');
            // $table->foreign('episode_id')->references('id')->on('episodes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_chunks');
    }
};
