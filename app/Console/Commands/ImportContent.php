<?php

namespace App\Console\Commands;

use App\Support\ContentTransfer;
use Illuminate\Console\Command;
use Illuminate\Console\View\TaskResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ImportContent extends Command
{
    protected $signature = 'content:import
        {sql : The content-*.sql.gz file from content:export}
        {uploads? : The uploads-*.tar.gz file from content:export}
        {--force : Replace the content of a database that already has some}';

    protected $description = 'Import database content and uploaded files exported with content:export';

    public function handle(): int
    {
        $sqlFile = $this->resolve($this->argument('sql'));
        $uploadsFile = $this->argument('uploads') ? $this->resolve($this->argument('uploads')) : null;

        try {
            $config = ContentTransfer::connection();
            foreach (array_filter([$sqlFile, $uploadsFile]) as $file) {
                if (! is_file($file)) {
                    throw new RuntimeException("File not found: {$file}");
                }
            }
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        // Never wipe a live site by accident
        if (! $this->option('force') && Schema::hasTable('destinations') && DB::table('destinations')->exists()) {
            $this->error('This database already has content. Importing would replace it, including anything added on this site since.');
            $this->line('  Run again with <fg=yellow>--force</> if that is what you want.');

            return self::FAILURE;
        }

        try {
            ContentTransfer::requireBinary('mysql');
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $error = null;
        $this->components->task('Importing database', function () use ($config, $sqlFile, &$error) {
            $command = 'set -o pipefail; gunzip -c ' . escapeshellarg($sqlFile)
                . ' | mysql ' . ContentTransfer::clientArguments($config) . ' ' . escapeshellarg($config['database']);
            $result = Process::env(ContentTransfer::environment($config))->timeout(600)->run(['bash', '-c', $command]);
            DB::purge();

            if ($result->failed()) {
                $error = $this->describeFailure($result->errorOutput());
            }

            return $error === null ? TaskResult::Success->value : TaskResult::Failure->value;
        });

        if ($error) {
            $this->error($error);

            return self::FAILURE;
        }

        if ($uploadsFile) {
            $this->components->task('Unpacking uploaded files', function () use ($uploadsFile) {
                if (! is_dir(storage_path('app/public'))) {
                    mkdir(storage_path('app/public'), 0755, true);
                }
                Process::timeout(600)->run(['tar', '-xzf', $uploadsFile, '-C', storage_path('app/public')])->throw();

                return true;
            });
        }

        $this->components->task('Linking storage and clearing caches', function () {
            if (! file_exists(public_path('storage'))) {
                $this->callSilently('storage:link');
            }
            $this->callSilently('optimize:clear');

            return true;
        });

        $this->newLine();
        $this->table(['Table', 'Rows'], collect(ContentTransfer::rowCounts())->map(fn ($rows, $table) => [$table, number_format($rows)])->values());
        $this->line('  Compare these counts with the export. Then run <fg=gray>php artisan migrate --force</> (it should have nothing to do).');

        return self::SUCCESS;
    }

    /**
     * The database's own error, not gunzip complaining that its reader stopped.
     */
    protected function describeFailure(string $output): string
    {
        $message = collect(explode("\n", trim($output)))
            ->reject(fn ($line) => str_starts_with($line, 'gunzip:'))
            ->implode("\n") ?: trim($output);

        if (str_contains($message, 'Authentication plugin')) {
            $message .= "\n\nThis `mysql` client is newer than the database server. Run the import on the server itself,"
                . " where its own client is installed, or import the file with phpMyAdmin.";
        }

        return $message;
    }

    /**
     * Accept a full path, a path relative to the project, or just the file
     * name of something in storage/app/exports.
     */
    protected function resolve(string $path): string
    {
        foreach ([$path, base_path($path), ContentTransfer::exportDirectory() . '/' . $path] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return $path;
    }
}
