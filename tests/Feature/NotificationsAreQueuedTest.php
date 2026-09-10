<?php

use Illuminate\Contracts\Queue\ShouldQueue;
use Symfony\Component\Finder\Finder;

// Every notification must be queued — a synchronous SMTP call inside a request thread
// is what turns one slow mail server into a hung/timed-out response for the user.
// See app/Notifications/*.php and the "Production Checklist" section of README.md.
test('every notification class implements ShouldQueue', function () {
    $files = Finder::create()->files()->name('*.php')->in(app_path('Notifications'));

    $classes = collect($files)->map(
        fn ($file) => 'App\\Notifications\\'.$file->getBasename('.php'),
    );

    expect($classes)->not->toBeEmpty();

    foreach ($classes as $class) {
        expect(is_subclass_of($class, ShouldQueue::class))
            ->toBeTrue("{$class} does not implement ShouldQueue.");
    }
});
