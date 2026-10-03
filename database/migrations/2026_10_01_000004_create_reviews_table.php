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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            // 評価値（1〜5）の範囲はFormRequestでバリデーションする
            $table->tinyInteger('rating');
            $table->string('comment');
            $table->timestamps();

            // 1人1冊1レビュー（同じユーザーが同じ書籍に重複投稿できないようにする）
            $table->unique(['user_id', 'book_id']);
        });
    }

    /**
     * マイグレーションを元に戻す。
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
