<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * إنشاء أو تحديث الملف الشخصي للمستخدم المصادق عليه.
     */
    public function createOrUpdate(Request $request)
    {
        // --- 1. الحصول على المستخدم الحالي من الطلب ---
        // لارافيل يقوم بهذا تلقائيًا بفضل middleware 'auth:sanctum'
        $user = $request->user();

        // --- 2. التحقق من صحة البيانات القادمة ---
        $validator = Validator::make($request->all(), [
            'Skills' => 'required|string',
            'Experience' => 'required|string',
            'Rating' => 'required|numeric|min:0|max:5', // قواعد تحقق أفضل
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // --- 3. استخدام دالة updateOrCreate لإنشاء أو تحديث الملف الشخصي ---
        // هذه الدالة ذكية: إذا لم يجد ملفًا شخصيًا للمستخدم، سينشئ واحدًا. إذا وجده، سيقوم بتحديثه.
        $profile = $user->profile()->updateOrCreate(
            ['id' => $user->id], // ابحث عن بروفايل بهذا الـ user_id
            [
                'Skills' => $request->Skills,       // ✅ تم التصحيح
                'Experience' => $request->Experience,
                'Rating' => $request->Rating,
            ]
        );

        // --- 4. إرجاع استجابة ناجحة ---
        return response()->json([
            'message' => 'Profile successfully updated/created.',
            'profile' => $profile
        ], 200); // 200 OK هو الرمز الأنسب هنا
    }
}
