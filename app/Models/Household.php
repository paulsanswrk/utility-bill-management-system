<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Household extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
    ];

    public static function getHouseholdsOfUser(int $userId)
    {
        return Household::all()->where('user_id', '=', $userId)->select('id', 'name')->sortBy('name')->values();
    }
}
