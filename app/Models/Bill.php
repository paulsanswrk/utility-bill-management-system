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

    protected $fillable = [
        'user_id',
        'household_id',
        'company_id',
        'bill_date',
        'payment_date',
        'amount',
        'paid',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid' => 'boolean',
    ];

//    protected $with = ['company', ]; //'household'

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(UtilityCompany::class);
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function get_bill_pdf_path(string $doc_type)
    {
        $utility_company = UtilityCompany::find($this->company_id);

        if (empty($utility_company)) return false;

        switch ($doc_type) {
            case 'bill':
                $doc_name = "bill";
                break;
            case 'payment_confirmation':
                $doc_name = "payment_confirmation";
                break;
        }

        return "private/bills/user_{$this->user_id}/household_{$this->household_id}/{$this->bill_date}/{$utility_company->name}/{$doc_name}_{$this->id}.pdf";
    }
}
