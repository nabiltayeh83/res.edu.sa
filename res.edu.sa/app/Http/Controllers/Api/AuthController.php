<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // ✅ إنشاء حساب جديد
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = auth()->login($user);
        return $this->respondWithToken($token, $user);
    }

    // ✅ تسجيل الدخول
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');
        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
        }

        return $this->respondWithToken($token, auth()->user());
    }

    // ✅ تسجيل الخروج
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }

    // ✅ تجديد الـ Token (Refresh)
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh(), auth()->user());
    }

    // ✅ بيانات المستخدم الحالي
    public function me()
    {
        return response()->json(auth()->user());
    }

    // ✅ طلب استرجاع كلمة المرور
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'تم إرسال رابط الاسترجاع على بريدك'])
            : response()->json(['message' => 'البريد غير موجود'], 404);
    }

    // ✅ إعادة تعيين كلمة المرور
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])
                     ->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'تم تغيير كلمة المرور بنجاح'])
            : response()->json(['message' => 'فشل في إعادة التعيين'], 400);
    }

    // Helper
    private function respondWithToken($token, $user)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth()->factory()->getTTL() * 60,
            'user'         => $user,
        ]);
    }
}
