<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destination_tour', function (Blueprint $table) {
            $table->foreignId('destination_id')->constrained()->onDelete('cascade');
            $table->foreignId('tour_id')->constrained()->onDelete('cascade');
            $table->boolean('is_primary')->default(false);
            $table->primary(['destination_id', 'tour_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destination_tour');
    }
};