<?php

use Illuminate\Support\Facades\Artisan;

// The actual dump-and-zip pipeline is verified manually (README's "Backups"
// section documents the restore-dry-run procedure) since it shells out to
// pg_dump and writes real files — not something worth exercising in the
// automated suite. These tests only guard the configuration itself.

test('the backup package is registered and its commands are available', function () {
    $commands = array_keys(Artisan::all());

    expect($commands)->toContain('backup:run', 'backup:clean', 'backup:list', 'backup:monitor');
});

test('backups are written to a disk outside the project directory, not the default local disk', function () {
    expect(config('backup.backup.destination.disks'))->toBe(['backups']);
    expect(config('filesystems.disks.backups.root'))->not->toStartWith(base_path());
});

test('the backup source includes uploaded files, not just the database', function () {
    $include = config('backup.backup.source.files.include');

    expect($include)->toContain(storage_path('app/private'));
    expect($include)->toContain(storage_path('app/public'));
});
