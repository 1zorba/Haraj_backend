<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    /**
     * الخطوة 1: إرسال كود التحقق إلى البريد الإلكتروني
     */
    public function sendOtp(Request $request)
    {  
    
 
        // 1. التحقق من وجود البريد الإلكتروني
        $request->validate(['email' => 'required|email|exists:users,email']);

        // 2. إنشاء كود عشوائي من 6 أرقام
        $otp = rand(100000, 999999);

        // 3. حذف أي أكواد قديمة لنفس البريد
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // 4. تخزين الكود الجديد في قاعدة البيانات مع تاريخ انتهاء الصلاحية (مثلاً، 10 دقائق)
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $otp, // سنستخدم حقل التوكن لتخزين الكود
            'created_at' => Carbon::now()
        ]);

        // 5. إرسال البريد الإلكتروني
        try {
            Mail::raw("كود استعادة كلمة المرور الخاص بك هو: $otp", function ($message) use ($request) {
                $message->to($request->email);
                $message->subject('استعادة كلمة المرور');
            });
        } catch (\Exception $e) {
            // في حال فشل الإرسال، أرجع رسالة خطأ واضحة
            return response()->json([
                'message' => 'فشل إرسال البريد الإلكتروني. يرجى التحقق من إعدادات .env وسجل الأخطاء.',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json(['message' => 'تم إرسال كود التحقق بنجاح.'], 200);
    }

    /**
     * الخطوة 2: التحقق من صحة الكود
     */
    public function verifyOtp(Request $request)
    {
        // 1. التحقق من البيانات
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric|digits:6',
        ]);

        // 2. البحث عن الكود في قاعدة البيانات
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->otp)
            ->first();

        // 3. التحقق من وجود الكود ومن صلاحيته (لم يمر أكثر من 10 دقائق)
        if (!$resetRecord || Carbon::parse($resetRecord->created_at)->addMinutes(10)->isPast()) {
            return response()->json(['message' => 'الكود غير صالح أو منتهي الصلاحية.'], 400);
        }

        // الكود صحيح
        return response()->json(['message' => 'تم التحقق من الكود بنجاح.'], 200);
    }

    /**
     * الخطوة 3: إعادة تعيين كلمة المرور
     */
    public function resetPassword(Request $request)
    {
        // 1. التحقق من البيانات
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric|digits:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // 2. إعادة التحقق من الكود مرة أخرى كخطوة أمان إضافية
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->otp)
            ->first();

        if (!$resetRecord || Carbon::parse($resetRecord->created_at)->addMinutes(10)->isPast()) {
            return response()->json(['message' => 'الكود غير صالح أو منتهي الصلاحية.'], 400);
        }

        // 3. تحديث كلمة المرور في جدول users
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // 4. حذف الكود من قاعدة البيانات لمنع إعادة استخدامه
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'تم تغيير كلمة المرور بنجاح!'], 200);
    }
}
