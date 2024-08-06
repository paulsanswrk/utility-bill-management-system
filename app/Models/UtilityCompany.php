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
    ];

    public static function getCompaniesOfUser(int $userId)
    {
        return UtilityCompany::all()->where('user_id', '=', $userId)->values();
    }
}
