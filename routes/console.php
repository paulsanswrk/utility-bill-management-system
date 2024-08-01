<?php

use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::call(function () {
    DB::table('bills')->whereTime('created_at', '<', Carbon::today()->subtract('day', 1))
        ->whereNull('data')->delete();
})->daily();

