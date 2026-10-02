<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accommodations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['hotel', 'hostel', 'villa', 'resort', 'guesthouse']);
            $table->longText('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('price_from', 12, 2)->nullable();
            $table->string('currency', 3)->default('IDR');
            $table->integer('star_rating')->nullable();
            $table->longText('amenities')->nullable();
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
        Schema::dropIfExists('accommodations');
    }
};