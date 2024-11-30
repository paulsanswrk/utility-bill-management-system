<?php

use App\Jobs\ProcessBillNotifications;
use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
/*
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->everyMinute();*/

Schedule::call(function () {
    $del_cnt = DB::table('bills')
        ->whereTime('created_at', '<', Carbon::today()->subtract('day', 1))
        ->whereNull('company_id')->delete();
    Log::channel('ubms')->info("$del_cnt bills deleted");


})->hourly();

/*Schedule::call(function () {
    $deleted_files = [];
    collect(Storage::disk('private')->allFiles(Bill::pdf_tmp_upload_path))
        ->each(function($file) use ($deleted_files) {
            if ($file['type'] == 'file' && $file['timestamp'] < now()->subDays(15)->getTimestamp()) {
                Storage::disk('private')->delete($file['path']);
                $deleted_files[] = $file['path'];
            }
        });

    Log::channel('ubms')->info("deleted files: " . implode(", ", $deleted_files));
})->hourly();*/

//Schedule::job(new ProcessBillNotifications)->everyMinute();
//dailyAt('10:00')


Schedule::call(function () {
//    Log::channel('ubms')->info("Process Bill Notifications");
    $ProcessBillNotifications = new ProcessBillNotifications();
    $ProcessBillNotifications->handle();

    //email_change_requests cleanup
    $deleted_requests = DB::table('email_change_requests')
        ->where('expires_at', '>', Carbon::now())
        ->delete();
    Log::channel('ubms')->info("$deleted_requests email change requests deleted");

})->dailyAt('10:00');

