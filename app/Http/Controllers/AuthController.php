<?php

namespace App\Http\Controllers;
use App\Models\User; 
use Kreait\Firebase\Factory;  
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Mail\welcomemail;
use App\Models\JobRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth; 

class AuthController extends Controller
{
 
    public function register(Request $request)
    {
         $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|min:8',

             'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif',],
             'role' => 'required|string',
            'profession' => 'nullable|string|max:255',

        'latitude' => 'nullable|numeric|between:-90,90',
        'longitude' => 'nullable|numeric|between:-180,180',
        'address' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 2. معالجة الصورة
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
        }

        // 3. إنشاء المستخدم
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'image' => $imagePath,
            'role' => $request->role,
            'profession' => $request->profession,

        'latitude' => $request->latitude,
        'longitude' => $request->longitude,
        'address' => $request->address,
        ]);


         try {
            Mail::to($user->email)->send(new welcomemail());
        } catch (\Exception $e) {
            // لا توقف العملية إذا فشل الإيميل
        }

        // 5. إرجاع الاستجابة النهائية
        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }

    /**
     * دالة لتسجيل الدخول
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    /**
     * دالة لجلب بيانات المستخدم الحالي (مع ملفه الشخصي)
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $user->load('profile'); // تحميل علاقة الملف الشخصي
        return response()->json($user);
    }

    /**
     * دالة لجلب كل المستخدمين (للاختبار)
     */
    public function getalldata()
    {
        return User::with('profile')->get();
    }

    /**
      * دالة لجلب المستخدمين حسب المهنة
     */
 
 

public function getUsersByProfession($profession)
{
    $currentUserId = Auth::id(); // <-- احصل على ID المستخدم الحالي

    $users = User::where('profession', $profession)
                 ->where('id', '!=', $currentUserId)  
                 ->with('profile') // <-- تحسين: جلب بيانات البروفايل معهم
                 ->get();

    return response()->json($users);
}



        // * تحديث FCM token للمستخدم المسجل دخوله.
    
    public function updateFcmToken(Request $request)
    {
        // 1. التحقق من صحة البيانات المرسلة
        $validated = $request->validate([
            'fcm_token' => 'required|string',
        ]);

        try {
            // 2. الحصول على المستخدم الحالي المسجل دخوله
            $user = Auth::user();

            // 3. تحديث الحقل في قاعدة البيانات
            $user->fcm_token = $validated['fcm_token'];
            $user->save();

            // 4. إرجاع رسالة نجاح
            return response()->json([
                'message' => 'FCM token updated successfully.'
            ], 200);

        } catch (\Exception $e) {
            // في حال حدوث أي خطأ
            return response()->json([
                'message' => 'Failed to update FCM token.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
 



public function sendJobRequest(Request $request, $worker_id)
{
    try {
        // 1. الحصول على صاحب العمل الذي أرسل الطلب (المستخدم الحالي)
        $employer = Auth::user();

        // 2. إنشاء طلب العمل الجديد في قاعدة البيانات
        // هذا هو الجزء الذي يربط كل شيء
        $jobRequest = JobRequest::create([
            'employer_id' => $employer->id,
            'worker_id' => $worker_id,
            'status' => 'pending', // الحالة الافتراضية
        ]);

        // --- هنا يبدأ كود إرسال الإشعار (الذي يعمل بالفعل) ---
        $credentialsPath = 'C:/Users/USER/go/config/firebase_credentials.json';
        if (!file_exists($credentialsPath)) {
            return response()->json(['error' => 'CRITICAL: Firebase credentials file not found.'], 500);
        }
        $factory = (new Factory)->withServiceAccount($credentialsPath);
        $messaging = $factory->createMessaging();

        $worker = User::find($worker_id);
        if (!$worker || !$worker->fcm_token) {
            // حتى لو فشل الإشعار، الطلب تم إنشاؤه بنجاح
            return response()->json(['message' => 'Job request created, but notification could not be sent.'], 201);
        }

        // يمكنك الآن إرسال رسالة أكثر تفصيلاً
        $notificationMessage = 'لديك طلب عمل جديد من ' . $employer->name;

        $message = CloudMessage::withTarget('token', $worker->fcm_token)
            ->withNotification(Notification::create('طلب عمل جديد!', $notificationMessage))
            // **مهم جدًا:** أرسل معرف الطلب مع الإشعار
            ->withData(['job_request_id' => (string)$jobRequest->id, 'type' => 'job_request']);

        $messaging->send($message);
        // --- نهاية كود الإشعار ---

        return response()->json(['message' => 'Job request created and notification sent successfully!'], 201);

    } catch (\Throwable $e) {
        return response()->json([
            'error' => 'An unexpected error occurred while creating the job request.',
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
        ], 500);
    }
}


 


public function getMyJobRequests(Request $request)
{
    // 1. احصل على المستخدم المسجل دخوله
    $currentUser = Auth::user();

    // 2. ابحث عن كل الطلبات التي يكون فيها 'worker_id' هو ID المستخدم الحالي
    //    وقم بتحميل بيانات المرسل (صاحب العمل) مع كل طلب
    $jobRequests = JobRequest::where('worker_id', $currentUser->id)
                             ->with('employer') // <<< هذا مهم جدًا لجلب بيانات صاحب العمل
                             ->orderBy('created_at', 'desc') // رتب الطلبات من الأحدث إلى الأقدم
                             ->get();
    
    // 3. أرجع الطلبات كـ JSON
    return response()->json($jobRequests);
}


 
public function respondToJobRequest(Request $request, $id)
{
    // 1. التحقق من صحة البيانات
    $validated = $request->validate([
        'status' => 'required|string|in:accepted,rejected',
    ]);

    try {
        // 2. تحويل الـ ID إلى رقم صحيح
        $requestId = (int)$id;

        // 3. البحث عن الطلب
        $jobRequest = JobRequest::find($requestId);

        // 4. التحقق من وجود الطلب
        if (!$jobRequest) {
            return response()->json(['message' => 'Job request not found.'], 404);
        }

        // 5. التحقق من الصلاحية (أن المستخدم هو العامل المقصود)
        if (Auth::id() !== $jobRequest->worker_id) {
            return response()->json(['message' => 'You are not authorized to respond to this request.'], 403);
        }

        // 6. تحديث حالة الطلب
        $jobRequest->status = $validated['status'];
        $jobRequest->save();

        // 7. إرسال إشعار إلى صاحب العمل
        $employer = User::find($jobRequest->employer_id);
        if ($employer && $employer->fcm_token) {
            $worker = Auth::user();
            $status_in_arabic = ($validated['status'] == 'accepted') ? 'وافق' : 'رفض';
            $notificationTitle = 'تحديث على طلبك';
            $notificationBody = "$status_in_arabic العامل " . $worker->name . " على طلب العمل الذي أرسلته.";
            
            $this->sendFirebaseNotification(
                $employer->fcm_token,
                $notificationTitle,
                $notificationBody,
                ['job_request_id' => (string)$jobRequest->id, 'type' => 'request_response']
            );
        }

        return response()->json([
            'message' => 'Request status updated successfully!',
            'data' => $jobRequest
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'An error occurred while updating the request.',
            'message' => $e->getMessage()
        ], 500);
    }
}

// دالة مساعدة لإرسال الإشعارات (لتجنب تكرار الكود)
private function sendFirebaseNotification($token, $title, $body, $data = [])
{
    try {
        $credentialsPath = 'C:/Users/USER/go/config/firebase_credentials.json';
        if (!file_exists($credentialsPath)) return; // فشل صامت

        $factory = (new Factory)->withServiceAccount($credentialsPath);
        $messaging = $factory->createMessaging();

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        $messaging->send($message);
    } catch (\Throwable $e) {
     }
}



 
public function getSentJobRequests(Request $request)
{
    // 1. احصل على ID صاحب العمل المسجل دخوله حاليًا
    $employer_id = Auth::id();

    // 2. ابحث عن كل الطلبات التي أرسلها هذا المستخدم
    $requests = JobRequest::where('employer_id', $employer_id)
                          ->with('worker') // **مهم:** جلب بيانات العامل هذه المرة
                          ->orderBy('created_at', 'desc') // عرض الأحدث أولاً
                          ->get();

    // 3. أرسل القائمة كتنسيق JSON
    return response()->json($requests);
}

}


  