<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail, CanResetPassword
{
    use HasFactory, Notifiable;

    public function preferredLocale()
    {
        return $this->language ?? 'hr';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'language',
        'work_key_encrypted',
        'notifications',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        //added
        'work_key_encrypted',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    /**
     * Get the email change requests for the user.
     */
    public function email_change_request()
    {
        return $this->hasOne(EmailChangeRequest::class, 'id', 'id');
    }

    public function households()
    {
        return $this->belongsToMany(Household::class, 'household_user', 'user_id', 'household_id');
    }

/*    public function bills() //not working correctly
    {
        return $this->hasManyThrough(Bill::class, Household::class, 'user_id', 'household_id', 'id', 'id');
    }*/

}
