<?php

namespace App\Console\Commands;

use App\Support\ContentTransfer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class ExportContent extends Command
{
    protected $signature = 'content:export';

    protected $description = 'Export the database content and uploaded files, ready for content:import on another server';

    public function handle(): int
    {
        try {
            $config = ContentTransfer::connection();
            ContentTransfer::requireBinary('mysqldump');
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $directory = ContentTransfer::exportDirectory();
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $date = now()->format('Y-m-d-His');
        $sqlFile = "{$directory}/content-{$date}.sql.gz";
        $uploadsFile = "{$directory}/uploads-{$date}.tar.gz";

        $this->components->task('Exporting database', fn () => $this->dumpDatabase($config, $sqlFile));
        $this->components->task('Packing uploaded files', fn () => $this->packUploads($uploadsFile));

        $this->newLine();
        $this->table(['Table', 'Rows'], collect(ContentTransfer::rowCounts())->map(fn ($rows, $table) => [$table, number_format($rows)])->values());

        $this->components->twoColumnDetail('Database', str_replace(base_path() . '/', '', $sqlFile) . ' (' . ContentTransfer::humanSize($sqlFile) . ')');
        $this->components->twoColumnDetail('Uploads', str_replace(base_path() . '/', '', $uploadsFile) . ' (' . ContentTransfer::humanSize($uploadsFile) . ')');
        $this->newLine();
        $this->line('  Upload both files to the server, then run there:');
        $this->line('  <fg=gray>php artisan content:import ' . basename($sqlFile) . ' ' . basename($uploadsFile) . '</>');

        return self::SUCCESS;
    }

    /**
     * Structure of every table, plus the rows of every table except the
     * temporary ones (sessions, cache, jobs...), gzipped into one file.
     */
    protected function dumpDatabase(array $config, string $file): bool
    {
        $help = Process::run('mysqldump --help')->output();
        $options = collect([
            '--single-transaction',
            '--skip-comments',
            str_contains($help, 'no-tablespaces') ? '--no-tablespaces' : null,
            // Managed MySQL hosts reject GTID statements; MariaDB's mysqldump has no such option
            str_contains($help, 'set-gtid-purged') ? '--set-gtid-purged=OFF' : null,
        ])->filter()->implode(' ');

        $connection = ContentTransfer::clientArguments($config);
        $database = escapeshellarg($config['database']);
        $skipRows = collect(ContentTransfer::TRANSIENT_TABLES)
            ->map(fn ($table) => '--ignore-table=' . escapeshellarg("{$config['database']}.{$table}"))
            ->implode(' ');

        $command = "set -o pipefail; { mysqldump {$connection} {$options} --no-data {$database}"
            . " && mysqldump {$connection} {$options} --no-create-info --skip-triggers {$skipRows} {$database}; }"
            . ' | gzip -9 > ' . escapeshellarg($file);

        Process::env(ContentTransfer::environment($config))->timeout(600)->run(['bash', '-c', $command])->throw();

        return true;
    }

    protected function packUploads(string $file): bool
    {
        Process::timeout(600)->run(['tar', '-czf', $file, '-C', storage_path('app/public'), '.'])->throw();

        return true;
    }
}
