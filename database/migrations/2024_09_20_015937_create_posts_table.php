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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()
                ->onDelete('cascade');
            $table->foreignId('theme_id')->nullable()
                ->constrained('themes');
            $table->string('title', 64)->nullable();
            $table->text('content');
            $table->text('plain_text');
            $table->boolean('is_public')->default(false);
            $table->string('cover_image')->nullable();
            $table->boolean('is_shared')->default(false);
            $table->foreignId('shared_post_id')->nullable()
                ->constrained('posts')
                ->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
