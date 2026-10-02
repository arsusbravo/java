<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Shared rules for moving the site's content (database rows and uploaded
 * files) between installations with content:export and content:import.
 */
class ContentTransfer
{
    /** Tables whose rows are temporary, so only their structure is copied. */
    public const TRANSIENT_TABLES = [
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
    ];

    /** Tables summarised after an export or import, to compare both sides. */
    public const CONTENT_TABLES = [
        'regions', 'destinations', 'destination_types', 'accommodations', 'tours', 'restaurants',
        'articles', 'reviews', 'images', 'tags', 'affiliate_networks', 'affiliate_clicks', 'users',
    ];

    public static function connection(): array
    {
        $name = config('database.default');
        $config = config("database.connections.{$name}");

        if (! in_array($config['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("Content transfer needs a MySQL or MariaDB database; the current connection uses {$config['driver']}.");
        }

        return $config;
    }

    /**
     * Connection arguments shared by mysql and mysqldump. The password goes
     * in MYSQL_PWD (see environment()) so it never shows in a process list.
     */
    public static function clientArguments(array $config): string
    {
        return collect([
            '--host=' . $config['host'],
            '--port=' . $config['port'],
            '--user=' . $config['username'],
            '--default-character-set=utf8mb4',
        ])->map(fn ($argument) => escapeshellarg($argument))->implode(' ');
    }

    public static function environment(array $config): array
    {
        return ['MYSQL_PWD' => (string) $config['password']];
    }

    /**
     * Fail with a clear message when a command-line tool is missing.
     */
    public static function requireBinary(string $binary): void
    {
        if (! Process::run('command -v ' . escapeshellarg($binary))->successful()) {
            throw new RuntimeException("`{$binary}` was not found. Install the MySQL client tools on this machine first.");
        }
    }

    /**
     * Exact row counts of the content tables that exist.
     *
     * @return array<string, int>
     */
    public static function rowCounts(): array
    {
        return collect(self::CONTENT_TABLES)
            ->filter(fn ($table) => Schema::hasTable($table))
            ->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()])
            ->all();
    }

    public static function exportDirectory(): string
    {
        return storage_path('app/exports');
    }

    public static function humanSize(string $path): string
    {
        $bytes = filesize($path);

        return $bytes >= 1048576 ? round($bytes / 1048576, 1) . ' MB' : round($bytes / 1024) . ' KB';
    }
}
