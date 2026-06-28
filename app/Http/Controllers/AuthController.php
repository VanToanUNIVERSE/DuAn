<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
