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
            // 書籍が紐づいたジャンルは削除させない（削除の制限をDBでも保証する）
            $table->foreignId('genre_id')
                ->constrained('genres')
                ->restrictOnDelete();
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
