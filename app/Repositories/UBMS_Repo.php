<?php


namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class UBMS_Repo {
    public function get_user_bills(int $userId)
    {
        $bills = DB::select("SELECT b.id,
       b.household_id,
       b.company_id                                          AS utility_company_id,
       c.name                                                AS utility_company_name,
       b.amount,
       b.paid,
       b.bill_date,
       b.payment_date,
       b.bill_pdf_path,
       b.payment_confirmation_pdf_path,
       b.cipher_key_encrypted,
       hh.name household_name
FROM `bills` b
         INNER JOIN `households` hh ON hh.`id` = b.`household_id`
         INNER JOIN `household_user` hu ON hu.`household_id` = hh.`id`
         INNER JOIN `utility_companies` c ON b.`company_id` = c.`id`
WHERE hu.`user_id` = $userId");

        return $bills;

    }
}
