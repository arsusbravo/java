<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->text('short_description')->nullable();
            $table->enum('type', ['day_tour', 'multi_day', 'private', 'group', 'adventure', 'cultural']);
            $table->integer('duration')->nullable();
            $table->enum('duration_unit', ['hours', 'days'])->default('hours');
            $table->decimal('price_from', 12, 2)->nullable();
            $table->string('currency', 3)->default('IDR');
            $table->enum('difficulty_level', ['easy', 'moderate', 'hard'])->nullable();
            $table->integer('max_group_size')->nullable();
            $table->longText('included')->nullable();
            $table->longText('not_included')->nullable();
            $table->string('meeting_point')->nullable();
            $table->text('affiliate_link')->nullable();
            $table->string('affiliate_network')->nullable();
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->integer('views_count')->default(0);
            $table->integer('clicks_count')->default(0);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};