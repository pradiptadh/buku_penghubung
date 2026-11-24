<?php

use Illuminate\Support\Facades\Schedule;

/*
 | Pengganti tick() prototipe yang berjalan setiap detik:
 | cukup satu cron per server (php artisan schedule:run).
 */
Schedule::command('safety:check')->everyMinute();
Schedule::command('pickup:remind')->everyMinute();
