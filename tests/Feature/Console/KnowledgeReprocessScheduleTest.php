<?php

use Illuminate\Console\Scheduling\Schedule;

test('knowledge reprocess failed-doc retry is scheduled hourly', function () {
    $events = app(Schedule::class)->events();

    $target = collect($events)->first(function ($event) {
        return str_contains($event->command, 'knowledge:reprocess --status=failed');
    });

    expect($target)->not->toBeNull();
    expect($target?->expression)->toBe('0 * * * *');
});
