<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Household extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

/*    public static function getHouseholdsOfUser(int $userId)
    {
        return Household::all()->where('user_id', '=', $userId)->select('id', 'name')->sortBy('name')->values();
    }*/

    public function users()
    {
        return $this->belongsToMany(User::class, 'household_user', 'household_id', 'user_id');
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function utility_companies()
    {
        return $this->hasMany(UtilityCompany::class);
    }

}
