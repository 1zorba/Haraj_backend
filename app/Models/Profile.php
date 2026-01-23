<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasFactory;

   
    protected $fillable = ['id', 'Skills', 'Experience', 'Rating'];

    /**
     * Get the user that owns the profile.
     * 
     * كل ملف شخصي يتبع لمستخدم واحد
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

