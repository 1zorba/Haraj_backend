<?php

// app/Policies/AdminPolicy.php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminPolicy
{
    use HandlesAuthorization;

    public function accessAdminDashboard(User $user)
    {
        // السماح بالوصول فقط إذا كان دور المستخدم هو 'admin'
        return $user->isAdmin();
    }
}
























