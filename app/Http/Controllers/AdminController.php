<?php

// app/Http/Controllers/AdminController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Policies\AdminPolicy; // استيراد السياسة

class AdminController extends Controller
{
    public function __construct()
    {
        // تطبيق السياسة على جميع دوال المتحكم
        $this->middleware('auth:sanctum'); // تأكد من أن المستخدم مسجل الدخول
    }

    public function index()
    {
        // التحقق من الصلاحية باستخدام السياسة
        $this->authorize('accessAdminDashboard', auth()->user());

        // إذا مر التحقق، يتم إرجاع بيانات لوحة التحكم
        return response()->json([
            'status' => 'success',
            'message' => 'Welcome to the Admin Dashboard!',
            'stats' => [
                'total_users' => \App\Models\User::count(),
                'pending_jobs' => \App\Models\JobRequest::where('status', 'pending')->count(),
                // ... إحصائيات أخرى
            ]
        ]);
    }
}

