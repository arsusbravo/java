<?php

use App\Support\ContentTransfer;

test('temporary tables are never treated as content', function () {
    expect(array_intersect(ContentTransfer::TRANSIENT_TABLES, ContentTransfer::CONTENT_TABLES))->toBe([])
        ->and(ContentTransfer::TRANSIENT_TABLES)->toContain('sessions', 'cache', 'jobs')
        ->and(ContentTransfer::CONTENT_TABLES)->toContain('destinations', 'accommodations', 'images', 'users');
});

test('export and import explain that they need MySQL', function (string $command, array $arguments) {
    // The test suite runs on SQLite; the real round trip is verified against MySQL
    $this->artisan($command, $arguments)
        ->expectsOutputToContain('needs a MySQL or MariaDB database')
        ->assertFailed();
})->with([
    'export' => ['content:export', []],
    'import' => ['content:import', ['sql' => 'missing.sql.gz']],
])->skip(fn () => in_array(config('database.connections.' . config('database.default') . '.driver'), ['mysql', 'mariadb']), 'Running on MySQL');

test('row counts cover the content tables that exist', function () {
    $this->seed(Database\Seeders\DatabaseSeeder::class);

    expect(ContentTransfer::rowCounts())
        ->toHaveKeys(['regions', 'destinations', 'users'])
        ->and(ContentTransfer::rowCounts()['regions'])->toBe(3);
});
