<?php

use Illuminate\Support\Facades\Schedule;

// Sync TikTok products mỗi 30 phút
Schedule::command('tiktok:sync-products --all')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/tiktok-sync.log'));

// Sync TikTok products mỗi 2 giờ (backup)
Schedule::command('tiktok:sync-products --all --force')
    ->everyTwoHours()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/tiktok-sync-force.log'));

// Clean up old logs mỗi ngày
Schedule::command('log:clear')
    ->daily()
    ->at('02:00');
