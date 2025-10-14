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
        Schema::table('entertainments', function (Blueprint $table) {
            $table->text('ai_summary')->nullable()->after('description');
        });

        Schema::table('episodes', function (Blueprint $table) {
            $table->text('ai_summary')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entertainments', function (Blueprint $table) {
            $table->dropColumn('ai_summary');
        });

        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn('ai_summary');
        });
    }
};
