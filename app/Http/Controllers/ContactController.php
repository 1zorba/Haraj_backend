<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function submit(Request $request)
    {
        // 1. التحقق من صحة البيانات
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. إرسال البريد الإلكتروني
        try {
            // يمكنك تغيير البريد الإلكتروني هنا إلى بريد الدعم الفني الخاص بك
            $recipientEmail = 'support@yourdomain.com'; 
            
            // استخدام دالة Mail::raw لإرسال رسالة نصية بسيطة
            Mail::raw("From: {$request->name} <{$request->email}>\nSubject: {$request->subject}\n\nMessage:\n{$request->message}", function ($message) use ($request, $recipientEmail) {
                $message->to($recipientEmail)
                        ->subject('New Contact Form Submission: ' . $request->subject);
                // تعيين عنوان الرد ليكون بريد المرسل
                $message->replyTo($request->email, $request->name);
            });

            return response()->json(['message' => 'Message sent successfully!'], 200);

        } catch (\Exception $e) {
            // إذا فشل الإرسال (مثلاً بسبب إعدادات SMTP الخاطئة)
            return response()->json([
                'message' => 'Failed to send email. Please check your MAIL_ settings.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
