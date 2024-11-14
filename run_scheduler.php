<?php

//file_put_contents(__DIR__ . 'log.txt', date('Y-m-d H:i:s') . "run_scheduler.php\n", FILE_APPEND);

//require __DIR__ . '/bootstrap/autoload.php';
define('LARAVEL_START', microtime(true));

file_put_contents(__DIR__ . '/storage/logs/scheduler.log', date('Y-m-d H:i:s') . ' ' . PHP_VERSION . PHP_EOL, FILE_APPEND);


try {
    require __DIR__ . '/vendor/autoload.php';
    require __DIR__ . '/bootstrap/app.php';

    $n = Artisan::call('schedule:run');
    file_put_contents(__DIR__ . '/storage/logs/scheduler.log', date('Y-m-d H:i:s') . " run_scheduler.php: $n" . PHP_EOL, FILE_APPEND);

} catch (Throwable $e) {
    file_put_contents(__DIR__ . '/storage/logs/scheduler.log', date('Y-m-d H:i:s') . " run_scheduler.php: {$e->getMessage()} file {$e->getFile()} line {$e->getLine()} {$e->getTraceAsString()}" . PHP_EOL, FILE_APPEND);

}

