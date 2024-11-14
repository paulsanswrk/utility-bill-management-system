<?php

namespace App\Jobs;

use App\Mail\BillNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBillNotifications implements ShouldQueue
{

    const notification_day = 14;
    const notification_dow = 1;

    use Dispatchable, InteractsWithQueue, \Illuminate\Bus\Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //get users to be notified

        $isNotificationDay = $this->isNotificationDay();
        $isNotificationDOW = $this->isNotificationDayOfWeek();


        $users = DB::select("SELECT distinct u.id, u.name, u.email, u.language, u.notifications
FROM users u
         JOIN bills b ON u.id = b.user_id
WHERE b.paid = 0
  AND b.bill_date < DATE_FORMAT(CURDATE(), '%Y-%m')
  AND (u.notifications = 'daily' or (u.notifications = 'weekly' and $isNotificationDOW = 1) or (u.notifications = 'monthly' and $isNotificationDay = 1))
  ");

        foreach ($users as $user) {
            //get all unpaid bills

            $bills = DB::select("SELECT b.bill_date issue_date, b.amount, uc.name as utility_company, h.name as household
FROM bills b
left join utility_companies uc on uc.id = b.company_id
left join households h on h.id = b.household_id
WHERE b.user_id = $user->id AND paid = 0
  AND b.bill_date < DATE_FORMAT(CURDATE(), '%Y-%m')
");

            // Notify each user
            \Mail::to($user->email)->send(new BillNotification($user, $bills));

            // Placeholder for notification logic
            Log::channel('ubms')->info("Notification sent to user: " . $user->id);
        }

        Log::info('Example job executed successfully.');
        Log::channel('ubms')->info("ProcessBillNotifications started");
//        echo "ProcessBillNotifications started";
    }

    /**
     * @return int
     */
    public function isNotificationDay(): int
    {
//        return 1;
        return now()->day == self::notification_day ? 1 : 0;
    }

    /**
     * @return int
     */
    public function isNotificationDayOfWeek(): int
    {
//        return 1;
        return now()->dayOfWeek == self::notification_dow ? 1 : 0;
    }
}
