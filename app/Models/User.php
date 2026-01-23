<?php

namespace App\Models;

 use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

 class User extends Authenticatable
{
     use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone', 
        'image', 
        'role', 
        'profession',
        'fcm_token',
        'latitude',
        'longitude',
        'address',  

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    // 4. مصفوفة appends لإضافة الحقل الافتراضي
    protected $appends = ['image_url'];

    // 5. العلاقة مع جدول profiles
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class, 'user_id', 'id');
    }

    // 6. الـ Accessor الذي يبني رابط الصورة
protected function imageUrl(): Attribute
{
    return Attribute::make(
        get: function () {
            if (!$this->image) return null;
            
            // إذا كان المسار المخزن يحتوي بالفعل على كلمة images، نستخدمه كما هو
            if (str_contains($this->image, 'images/')) {
                return asset('storage/' . $this->image);
            }
            
            // إذا كان المسار هو اسم الصورة فقط، نضيف كلمة images/ قبلها
            return asset('storage/images/' . $this->image);
        }
    );
}


    // ▼▼▼ الدوال المساعدة للتحقق من الدور ▼▼▼
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isTeacher()
    {
        return $this->role === 'teacher';
    }

    public function isWorker()
    {
        return $this->role === 'worker';
    }
    public function isEmploee()
    {
        return $this->role === 'employee';
    }

}
