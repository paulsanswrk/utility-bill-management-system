<?php

use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::call(function () {
    $del_cnt = DB::table('bills')
        ->whereTime('created_at', '<', Carbon::today()->subtract('day', 1))
        ->whereNull('company_id')->delete();
    Log::channel('ubms')->info("$del_cnt bills deleted");
})->hourly();

Schedule::call(function () {
    $deleted_files = [];
    collect(Storage::disk('private')->allFiles(Bill::pdf_tmp_upload_path))
        ->each(function($file) use ($deleted_files) {
            if ($file['type'] == 'file' && $file['timestamp'] < now()->subDays(15)->getTimestamp()) {
                Storage::disk('private')->delete($file['path']);
                $deleted_files[] = $file['path'];
            }
        });

    Log::channel('ubms')->info("deleted files: " . implode(", ", $deleted_files));
})->hourly();

