<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Facts from the Agoda hotel feed, shown on the hotel page (filled by `agoda:import`).
     */
    public function up(): void
    {
        Schema::table('accommodations', function (Blueprint $table) {
            $table->string('check_in_time', 5)->nullable()->after('address');
            $table->string('check_out_time', 5)->nullable()->after('check_in_time');
            $table->unsignedSmallInteger('room_count')->nullable()->after('check_out_time');
            $table->unsignedSmallInteger('year_opened')->nullable()->after('room_count');
            $table->unsignedSmallInteger('year_renovated')->nullable()->after('year_opened');
            $table->string('chain_name', 100)->nullable()->after('year_renovated');
        });
    }

    public function down(): void
    {
        Schema::table('accommodations', function (Blueprint $table) {
            $table->dropColumn(['check_in_time', 'check_out_time', 'room_count', 'year_opened', 'year_renovated', 'chain_name']);
        });
    }
};
