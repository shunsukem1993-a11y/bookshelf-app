<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * マイグレーションを実行する。
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->renameColumn('published_at', 'published_date');
        });
    }

    /**
     * マイグレーションを元に戻す。
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->renameColumn('published_date', 'published_at');
        });
    }
};
