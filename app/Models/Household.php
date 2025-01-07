<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

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

    public static function get_user_households()
    {
        return \auth()->user()->households()->select('households.id', 'households.name')->orderBy('name')->get();
    }

    public static function add_user_households(int $user_id, string $household_ids)
    {
        try {
            DB::statement("insert into household_user(user_id, household_id, created_at, updated_at)
    select $user_id, id, now(), now()
    from households
    where id in ($household_ids)");
        } catch (UniqueConstraintViolationException $e) {

        }
    }

}
