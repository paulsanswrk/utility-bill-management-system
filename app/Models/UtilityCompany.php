<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class UtilityCompany extends Model
{
    use HasFactory;
    use HasApiTokens;
    public $timestamps = false;

    protected $fillable = [
        'name',
        'household_id',
    ];

    public static function getCompaniesForHH(Household|int $household)
    {
        if (is_int($household)) {
            $household = Household::find($household);
        }

        return $household->utility_companies()->select('utility_companies.id', 'utility_companies.name')->orderBy('name')->get();
//        return UtilityCompany::all()->where('household_id', '=', $household_id)->values();
    }
}
