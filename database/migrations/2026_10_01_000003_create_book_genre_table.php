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
        Schema::create('book_genre', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            // ジャンル削除時、紐づく書籍がある場合は削除を制限する（機能要件を優先しRESTRICTを採用）
            $table->foreignId('genre_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['book_id', 'genre_id']);
        });
    }

    /**
     * マイグレーションを元に戻す。
     */
    public function down(): void
    {
        Schema::dropIfExists('book_genre');
    }
};
