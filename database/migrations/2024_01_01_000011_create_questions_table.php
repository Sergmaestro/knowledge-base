<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->longText('content');
            $table->string('slug')->unique();
            $table->integer('order_index')->default(0);
            $table->timestamps();

            $table->index(['topic_id', 'order_index']);
            $table->index('title');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
