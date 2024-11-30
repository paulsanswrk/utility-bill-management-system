<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailChangeRequest extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'email_change_requests';

    protected $fillable = [
        'id',
        'new_email',
        'uuid',
        'expires_at',
    ];

/*    protected $casts = [
        'id' => 'string',
    ];*/

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }
}
