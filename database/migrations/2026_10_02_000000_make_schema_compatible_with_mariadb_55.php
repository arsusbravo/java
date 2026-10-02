<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes databases created before AppServiceProvider set a 191-character default
 * string length work on MariaDB 5.5 / MySQL < 5.7: indexed strings are shortened
 * to fit the 767-byte index limit, and JSON columns (MariaDB 10.2+) become longtext.
 * On a new database the columns already have these types, so nothing changes.
 */
return new class extends Migration
{
    /** Indexed string columns, as table => [column => nullable]. */
    private const INDEXED_STRINGS = [
        'users' => ['email' => false],
        'password_reset_tokens' => ['email' => false],
        'sessions' => ['id' => false],
        'cache' => ['key' => false],
        'cache_locks' => ['key' => false],
        'jobs' => ['queue' => false],
        'job_batches' => ['id' => false],
        'failed_jobs' => ['uuid' => false],
        'regions' => ['slug' => false],
        'destinations' => ['slug' => false],
        'destination_types' => ['slug' => false],
        'accommodations' => ['slug' => false, 'external_id' => true],
        'tours' => ['slug' => false],
        'restaurants' => ['slug' => false],
        'articles' => ['slug' => false],
        'tags' => ['slug' => false],
        'affiliate_networks' => ['slug' => false],
        'images' => ['imageable_type' => false],
        'reviews' => ['reviewable_type' => false],
        'affiliate_clicks' => ['clickable_type' => false],
        'taggables' => ['taggable_type' => false],
    ];

    /** JSON columns, all nullable. */
    private const JSON_COLUMNS = [
        'accommodations' => ['amenities'],
        'tours' => ['included', 'not_included'],
        'restaurants' => ['cuisine_type', 'opening_hours'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Lengths and JSON support only matter on MySQL and MariaDB
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (self::INDEXED_STRINGS as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column => $nullable) {
                    $blueprint->string($column, 191)->nullable($nullable)->change();
                }
            });
        }

        foreach (self::JSON_COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->longText($column)->nullable()->change();
                }
            });
        }
    }

    /**
     * The shorter columns hold every existing value and the longtext columns
     * hold the same data as JSON did, so there is nothing to undo.
     */
    public function down(): void {}
};
