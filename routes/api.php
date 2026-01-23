<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AdminController;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- المسارات العامة (لا تتطلب تسجيل دخول) ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/users/profession/{profession}', [AuthController::class, 'getUsersByProfession']);

// مسارات استعادة كلمة المرور (يجب أن تكون عامة)
Route::post('/password/send-otp', [ForgotPasswordController::class, 'sendOtp']);
Route::post('/password/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
Route::post('/password/reset-with-otp', [ForgotPasswordController::class, 'resetPassword']);


// --- المسارات المحمية (تتطلب توكن مصادقة صالح) ---
Route::middleware('auth:sanctum')->group(function () {
    // حفظ توكن الإشعارات
    Route::post('/fcm-token', [NotificationController::class, 'storeToken']);

    // مسارات المستخدم والملف الشخصي
    Route::get('/user', [AuthController::class, 'profile']);
    Route::get('/user/{id}', function ($id) {
        $user = User::find($id);
        return $user ? response()->json($user) : response()->json(['message' => 'User not found'], 404);
    });

    // لوحة تحكم الأدمن (مثال: إحصائيات أو بيانات إضافية)
    Route::get('/admin/dashboard', [AdminController::class, 'index']);

    Route::post('/profile', [ProfileController::class, 'createOrUpdate']);
    Route::post('/fcm-token', [AuthController::class, 'updateFcmToken']);

    // مسارات طلبات العمل
    Route::post('/job-request/{worker_id}', [AuthController::class, 'sendJobRequest']);
    Route::post('/job-requests/{id}/respond', [AuthController::class, 'respondToJobRequest']);
    Route::get('/my-job-requests', [AuthController::class, 'getMyJobRequests']);
    Route::get('/sent-job-requests', [AuthController::class, 'getSentJobRequests']);

    // --- مسارات الدردشة ---
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    Route::get('/chat/conversations', [ChatController::class, 'getConversations']);
    Route::get('/chat/{userId}', [ChatController::class, 'getMessages']);

    // رابط تواصل معنا
    Route::post('/contact', [ContactController::class, 'submit']);

    // ✅ إحصائيات الأدمن (متوافقة مع Flutter)
    Route::get('/admin/stats', function () {
        return response()->json([
            'users_count' => User::count(),
            'messages_count' => Message::count(),
            'conversations_count' => Conversation::count(),
            'workers_count' => User::where('role', 'worker')->count(),
            'employers_count' => User::where('role', 'employer')->count(),
        ]);
    });
});


// ✅ جلب قائمة المستخدمين مع إمكانية الفلترة حسب الدور
Route::get('/admin/users', function (Request $request) {
    $role = $request->query('role');
    $query = User::query();
    if ($role) {
        $query->where('role', $role);
    }
    return response()->json([
        'status' => true,
        'users' => $query->latest()->get()
    ]);
});

// ✅ حذف مستخدم
Route::delete('/admin/users/{id}', function ($id) {
    $user = User::find($id);
    if (!$user) {
        return response()->json(['status' => false, 'message' => 'المستخدم غير موجود'], 404);
    }
    if ($user->role === 'admin') {
        return response()->json(['status' => false, 'message' => 'لا يمكن حذف مدير آخر'], 403);
    }
    $user->delete();
    return response()->json(['status' => true, 'message' => 'تم حذف المستخدم بنجاح']);
});



























// <?php

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\AuthController;
// use App\Http\Controllers\ProfileController;
// use App\Http\Controllers\ForgotPasswordController;
// use App\Http\Controllers\ChatController;
// use App\Http\Controllers\NotificationController;
// use App\Http\Controllers\ContactController;
// /*
// |--------------------------------------------------------------------------
// | API Routes
// |--------------------------------------------------------------------------
// */

// // --- المسارات العامة (لا تتطلب تسجيل دخول) ---
// Route::post('/register', [AuthController::class, 'register']);
// Route::post('/login', [AuthController::class, 'login']);
// Route::get('/users/profession/{profession}', [AuthController::class, 'getUsersByProfession']);

// // مسارات استعادة كلمة المرور (يجب أن تكون عامة)
// Route::post('/password/send-otp', [ForgotPasswordController::class, 'sendOtp']);
// Route::post('/password/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
// Route::post('/password/reset-with-otp', [ForgotPasswordController::class, 'resetPassword']);


// // --- المسارات المحمية (تتطلب توكن مصادقة صالح) ---
// Route::middleware('auth:sanctum')->group(function () {
//      Route::post('/fcm-token', [NotificationController::class, 'storeToken']);
//     // مسارات المستخدم والملف الشخصي
//     Route::get('/user', [AuthController::class, 'profile']);
//     Route::get('/user/{id}', function ($id) {
//         $user = \App\Models\User::find($id);
//         return $user ? response()->json($user) : response()->json(['message' => 'User not found'], 404);
//     });
//     Route::post('/profile', [ProfileController::class, 'createOrUpdate']);
//     Route::post('/fcm-token', [AuthController::class, 'updateFcmToken']);

//     // مسارات طلبات العمل
//     Route::post('/job-request/{worker_id}', [AuthController::class, 'sendJobRequest']);
//     Route::post('/job-requests/{id}/respond', [AuthController::class, 'respondToJobRequest']);
//     Route::get('/my-job-requests', [AuthController::class, 'getMyJobRequests']);
//     Route::get('/sent-job-requests', [AuthController::class, 'getSentJobRequests']);

//     // --- مسارات الدردشة (كلها محمية لأننا نحتاج لمعرفة المستخدم الحالي) ---
//     Route::post('/chat/send', [ChatController::class, 'sendMessage']);
//     Route::get('/chat/conversations', [ChatController::class, 'getConversations']);
//     Route::get('/chat/{userId}', [ChatController::class, 'getMessages']);

//     // ▼▼▼ رابط تواصل معنا الجديد ▼▼▼
//     Route::post('/contact', [ContactController::class, 'submit']);
// });
