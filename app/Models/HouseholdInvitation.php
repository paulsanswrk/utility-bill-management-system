<?php

namespace App\Models;

use App\Services\UBMS_Helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class HouseholdInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'invitee_id',
        'invitee_email',
        'invited_by',
        'household_ids',
        'invitation_status',
    ];

    public function invitee()
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function households()
    {
        $hh_ids = (new UBMS_Helper())->strToSortedIntArray($this->household_ids);
        return DB::table('households')->whereIn('id', $hh_ids)->get()->values();
    }


}
