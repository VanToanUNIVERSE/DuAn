<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // ─── Hiển thị form đăng nhập ────────────────────────────────────────────
    public function showLogin()
    {
        return view('auth.login');
    }

    // ─── Xử lý đăng nhập ────────────────────────────────────────────────────
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required'    => 'Vui lòng nhập email.',
            'email.email'       => 'Email không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended(route('suggest'));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Email hoặc mật khẩu không chính xác.']);
    }

    // ─── Hiển thị form đăng ký ──────────────────────────────────────────────
    public function showRegister()
    {
        return view('auth.register');
    }

    // ─── Xử lý đăng ký ──────────────────────────────────────────────────────
    public function register(Request $request)
    {
        $data = $request->validate([
            'fullName'     => ['required', 'string', 'max:100'],
            'student_code' => ['required', 'string', 'max:20', 'unique:users,student_code'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'password'     => ['required', 'confirmed', Password::min(8)],
        ], [
            'fullName.required'      => 'Vui lòng nhập họ và tên.',
            'student_code.required'  => 'Vui lòng nhập mã sinh viên.',
            'student_code.unique'    => 'Mã sinh viên này đã được sử dụng.',
            'email.required'         => 'Vui lòng nhập email.',
            'email.unique'           => 'Email này đã được đăng ký.',
            'password.required'      => 'Vui lòng nhập mật khẩu.',
            'password.confirmed'     => 'Xác nhận mật khẩu không khớp.',
            'password.min'           => 'Mật khẩu phải có ít nhất 8 ký tự.',
        ]);

        $user = User::create([
            'fullName'     => $data['fullName'],
            'username'     => $data['student_code'],
            'student_code' => $data['student_code'],
            'email'        => $data['email'],
            'password'     => Hash::make($data['password']),
        ]);

        Auth::login($user);

        return redirect()->route('suggest')
            ->with('success', 'Đăng ký thành công! Chào mừng ' . $user->fullName . '.');
    }

    // ─── Đăng xuất ──────────────────────────────────────────────────────────
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Bạn đã đăng xuất thành công.');
    }
}
