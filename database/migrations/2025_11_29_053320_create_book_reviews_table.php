<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('rating')->unsigned();
            $table->text('review');
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'book_id']); // 1ユーザー1書籍1レビュー
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_reviews');
    }
};