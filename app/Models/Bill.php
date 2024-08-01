<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bill extends Model
{
    use HasFactory;

//    public $timestamps = false;
    const pdf_tmp_upload_path = 'uploads/temp';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function get_bill_pdf_path(int $utility_company_id, string $bill_date, string $doc_type)
    {
        $utility_company = UtilityCompany::find($utility_company_id);

        if (empty($utility_company)) return false;

        switch ($doc_type) {
            case 'bill':
                $doc_name = "bill";
                break;
            case 'payment_confirmation':
                $doc_name = "payment_confirmation";
                break;
        }

        return "private/bills/user_{$this->user_id}/household_{$this->household_id}/$bill_date/{$utility_company->name}/{$doc_name}_{$this->id}.pdf";
    }
}
