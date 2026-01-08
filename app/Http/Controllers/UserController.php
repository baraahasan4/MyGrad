<?php

namespace App\Http\Controllers;

use App\Mail\EmailOtpMail;
use App\Models\Complaint;
use App\Models\EmailVerification;
use App\Models\Room;
use App\Models\Room_type;
use App\Models\Invoice;
use App\Models\Rating;
use App\Models\RefreshToken;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
         $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'phone' => 'required|string|max:255|unique:users',
        ]);

         if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 400);
        }

        // حفظ الصورة
        $photoUrl = null;

        if ($request->hasFile('photo')) {
            $fileName = time() . '.' . $request->photo->extension();
            $path = $request->photo->storeAs('public/user', $fileName);

             $photoUrl = asset('storage/user/' . $fileName);
        }

         $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'photo' => $photoUrl, // حفظ الرابط القابل للعرض
            'phone' => $request->phone
        ]);

        $this->createAndSendOtp($user->email);

    return response()->json(['message'=>'تم إنشاء الحساب. تحقق من بريدك لإدخال الكود.'], 201);

        //  $accessToken = $user->createToken('auth_token')->plainTextToken;
        //  // Refresh Token أولي (7 أيام مثلاً)
        // $refreshToken = bin2hex(random_bytes(32));
        // $user->refreshTokens()->create([
        //     'token'      => $refreshToken,
        //     'expires_at' => now()->addDays(7),
        // ]);


        // return response()->json([
        //     'user' => $user,
        //     'access_token'   => $accessToken,
        //     'refresh_token'  => $refreshToken,
        //     'message' => 'User registered successfully. Awaiting role assignment.'
        // ], 200);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (!$user->email_verified_at) {
        $this->createAndSendOtp($user->email);
        return response()->json(['message'=>'يرجى تفعيل البريد أولاً. تم إرسال رمز جديد.'],403);
    }

        $accessToken = $user->createToken('auth_token')->plainTextToken;
        // إصدار Refresh Token
        $refreshToken = bin2hex(random_bytes(32));
        $user->refreshTokens()->create([
            'token' => $refreshToken,
            'expires_at' => now()->addDays(7),
        ]);


        return response()->json([
            'user' => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ], 200);
    }


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logged out successfully',
        ], 200);
    }

    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        $refreshToken = RefreshToken::where('token', $request->refresh_token)
        ->where('expires_at', '>', now())
        ->first();

        if (!$refreshToken) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        // إصدار Access Token جديد
        $accessToken = $refreshToken->user->createToken('auth_token')->plainTextToken;

        // تحديث Refresh Token
        $newRefreshToken = bin2hex(random_bytes(32));
        $refreshToken->update([
            'token' => $newRefreshToken,
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken,
        ], 200);
    }

    private function createAndSendOtp(string $email)
{
    // حماية: لا ترسل أكثر من مرة خلال 60 ثانية
    $recent = EmailVerification::where('email',$email)->latest()->first();
    if ($recent && $recent->sent_at && now()->diffInSeconds($recent->sent_at) < 60) {
        return ['status'=>false, 'message'=>'يرجى الانتظار قبل إعادة الإرسال'];
    }

    $code = str_pad((string)random_int(0, 999999), 6, '0'); // 6 digits

    // خزّن OTP مُشفّر
    EmailVerification::create([
        'email' => $email,
        'otp' => Hash::make($code),
        'expires_at' => now()->addMinutes(10),
        'attempts' => 0,
        'sent_at' => now(),
    ]);

    // أرسل الإيميل
    Mail::to($email)->send(new EmailOtpMail($code));

    return ['status'=>true];
}

public function verifyOtp(Request $req)
{
    $req->validate(['email'=>'required|email','code'=>'required|digits:6']);

    $record = EmailVerification::where('email',$req->email)
                ->where('expires_at','>', now())
                ->latest()
                ->first();

    if (!$record) return response()->json(['message'=>'رمز غير موجود أو منتهي.'], 422);

    if ($record->attempts >= 5) return response()->json(['message'=>'تجاوزت عدد المحاولات'], 429);

    if (!Hash::check($req->code, $record->otp)) {
        $record->increment('attempts');
        return response()->json(['message'=>'رمز خاطئ'], 422);
    }

    // النجاح: فعّل المستخدم
    $user = User::where('email',$req->email)->firstOrFail();
    $user->email_verified_at = now();
    $user->save();

    // نظّف السجل (اختياري)
    $record->delete();

    // اصدر توكنات الآن
    $accessToken = $user->createToken('auth_token')->plainTextToken;
    $refreshToken = bin2hex(random_bytes(32));
    $user->refreshTokens()->create(['token'=>$refreshToken,'expires_at'=>now()->addDays(7)]);

    return response()->json(['access_token'=>$accessToken,'refresh_token'=>$refreshToken], 200);
}

public function resendOtp(Request $req)
{
    $req->validate(['email'=>'required|email']);
    $user = User::where('email',$req->email)->first();
    if (!$user) return response()->json(['message'=>'المستخدم غير موجود'],404);
    if ($user->email_verified_at) return response()->json(['message'=>'مفعل مسبقاً'],200);

    $res = $this->createAndSendOtp($user->email);
    if (!$res['status']) return response()->json(['message'=>$res['message']],429);
    return response()->json(['message'=>'تم إرسال الرمز الجديد.']);
}


    public function getMyInvoicesByStatus($status)
    {
        $user = auth()->user();

        if (!in_array($status, ['paid', 'unpaid'])) {
            return response()->json(['message' => 'Invalid status'], 400);
        }

        $invoices = $user->invoices()
        ->where('status', $status)
        ->orderBy('date', 'desc')
        ->get();

        return response()->json([
            'invoices' => $invoices
        ]);
    }

    public function uploadMyPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $user = Auth::user();

        $file = $request->file('photo');
        $filename = time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('images/users'), $filename);

        $user->photo = $filename;
        $user->save();

        return response()->json([
            'message' => 'تم رفع الصورة بنجاح.',
            'photo_url' => asset('images/users/' . $filename),
        ]);
    }

    public function addComplaintByUser(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:1000',
            'department' => 'required|in:rooms,massage,pool,restaurant,halls',
        ]);

        $complaint = Complaint::create([
            'description' => $request->description,
            'department' => $request->department,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'تم إرسال الشكوى بنجاح',
            'complaint' => $complaint,
        ], 201);
    }

    public function addRatingByUser(Request $request)
    {
        $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $rating = Rating::create([
            'rating' => $request->rating,
            'comment' => $request->comment,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'تم إرسال التقييم بنجاح',
            'rating' => $rating,
        ], 201);
    }

    public function profile()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'المستخدم غير مسجل دخول.'
            ], 401);
        }

        return response()->json([
            'name'        => $user->name,
            'email'       => $user->email,
            'phone'       => $user->phone,
            'national_id' => $user->national_id ? decrypt($user->national_id) : null,
        ]);
    }

}
