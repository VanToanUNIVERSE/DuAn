<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập – Hệ thống Kế hoạch Học tập</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-from: #0f0c29;
            --bg-mid:  #302b63;
            --bg-to:   #24243e;
            --glass-bg: rgba(255,255,255,0.07);
            --glass-border: rgba(255,255,255,0.15);
            --accent: #7c6af7;
            --accent-hover: #6358e8;
            --accent-glow: rgba(124,106,247,0.45);
            --text: #f1f1f5;
            --text-muted: rgba(241,241,245,0.55);
            --input-bg: rgba(255,255,255,0.06);
            --input-border: rgba(255,255,255,0.12);
            --input-focus: rgba(124,106,247,0.6);
            --error: #ff6b8a;
            --success: #56d38a;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg-from), var(--bg-mid), var(--bg-to));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        /* Animated orbs */
        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.35;
            animation: float 8s ease-in-out infinite alternate;
            pointer-events: none;
        }
        body::before {
            width: 500px; height: 500px;
            background: radial-gradient(circle, #7c6af7, transparent);
            top: -150px; left: -150px;
        }
        body::after {
            width: 400px; height: 400px;
            background: radial-gradient(circle, #ff6b8a, transparent);
            bottom: -100px; right: -100px;
            animation-delay: -4s;
        }

        @keyframes float {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(30px, 20px) scale(1.05); }
        }

        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2.75rem 2.5rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.1);
            position: relative;
            z-index: 1;
            animation: slideUp .5s cubic-bezier(.16,1,.3,1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Logo */
        .logo {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-bottom: 2rem;
        }
        .logo-icon {
            width: 46px; height: 46px;
            background: linear-gradient(135deg, #7c6af7, #ff6b8a);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.35rem;
            box-shadow: 0 4px 20px var(--accent-glow);
        }
        .logo-text {
            font-size: .85rem;
            font-weight: 600;
            color: var(--text);
            line-height: 1.3;
        }
        .logo-text span { color: var(--text-muted); font-weight: 400; font-size: .78rem; }

        h1 {
            font-size: 1.65rem;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -.5px;
            margin-bottom: .35rem;
        }
        .subtitle {
            font-size: .875rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
        }

        /* Alert */
        .alert {
            border-radius: 12px;
            padding: .85rem 1rem;
            font-size: .84rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: .55rem;
            animation: fadeIn .3s ease;
        }
        .alert-error  { background: rgba(255,107,138,.12); border: 1px solid rgba(255,107,138,.3); color: var(--error); }
        .alert-success{ background: rgba(86,211,138,.12);  border: 1px solid rgba(86,211,138,.3);  color: var(--success); }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }

        /* Form */
        .form-group { margin-bottom: 1.15rem; }

        label {
            display: block;
            font-size: .82rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: .45rem;
            letter-spacing: .3px;
        }

        .input-wrap { position: relative; }
        .input-icon {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            color: var(--text-muted);
            pointer-events: none;
            transition: color .2s;
        }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: .85rem 1rem .85rem 2.6rem;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 12px;
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: .9rem;
            transition: border-color .25s, box-shadow .25s, background .25s;
            outline: none;
        }
        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--input-focus);
            background: rgba(255,255,255,0.09);
        }
        input:focus + .input-icon, .input-wrap:focus-within .input-icon { color: var(--accent); }
        input::placeholder { color: rgba(241,241,245,0.3); }

        .field-error {
            font-size: .78rem;
            color: var(--error);
            margin-top: .35rem;
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        /* Remember me row */
        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        .remember-label {
            display: flex;
            align-items: center;
            gap: .5rem;
            cursor: pointer;
            font-size: .83rem;
            color: var(--text-muted);
        }
        input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--accent);
            cursor: pointer;
        }
        .forgot-link {
            font-size: .83rem;
            color: var(--accent);
            text-decoration: none;
            transition: opacity .2s;
        }
        .forgot-link:hover { opacity: .75; }

        /* Button */
        .btn {
            width: 100%;
            padding: .95rem;
            border: none;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform .18s, box-shadow .18s, opacity .18s;
            letter-spacing: .3px;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #9b6af7);
            color: #fff;
            box-shadow: 0 4px 20px var(--accent-glow);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px var(--accent-glow);
        }
        .btn-primary:active { transform: translateY(0); }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin: 1.5rem 0;
            color: var(--text-muted);
            font-size: .78rem;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--glass-border);
        }

        .switch-link {
            text-align: center;
            font-size: .85rem;
            color: var(--text-muted);
        }
        .switch-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            transition: opacity .2s;
        }
        .switch-link a:hover { opacity: .75; }

        /* Password toggle */
        .toggle-pw {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            font-size: .95rem;
            padding: 0;
            transition: color .2s;
        }
        .toggle-pw:hover { color: var(--text); }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <div class="logo-icon">🎓</div>
            <div class="logo-text">
                Hệ thống Kế hoạch Học tập<br>
                <span>IT Department – Academic Planner</span>
            </div>
        </div>

        <h1>Chào mừng trở lại</h1>
        <p class="subtitle">Đăng nhập để quản lý kế hoạch học tập của bạn</p>

        {{-- Thông báo thành công (sau logout, sau đăng ký redirect) --}}
        @if(session('success'))
            <div class="alert alert-success">
                <span>✅</span> {{ session('success') }}
            </div>
        @endif

        {{-- Lỗi chung --}}
        @if($errors->has('email'))
            <div class="alert alert-error">
                <span>⚠️</span> {{ $errors->first('email') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" id="loginForm">
            @csrf

            {{-- Email --}}
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-wrap">
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="sinhvien@email.com"
                        autocomplete="email"
                        required
                    >
                    <span class="input-icon">📧</span>
                </div>
            </div>

            {{-- Mật khẩu --}}
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <div class="input-wrap">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                    <span class="input-icon">🔒</span>
                    <button type="button" class="toggle-pw" onclick="togglePassword('password', this)" title="Hiện/ẩn mật khẩu">
                        👁️
                    </button>
                </div>
                @error('password')
                    <div class="field-error">⚠️ {{ $message }}</div>
                @enderror
            </div>

            {{-- Ghi nhớ & quên mật khẩu --}}
            <div class="remember-row">
                <label class="remember-label">
                    <input type="checkbox" name="remember" id="remember">
                    Ghi nhớ đăng nhập
                </label>
                <a href="#" class="forgot-link">Quên mật khẩu?</a>
            </div>

            <button type="submit" class="btn btn-primary" id="loginBtn">
                Đăng nhập
            </button>
        </form>

        <div class="divider">hoặc</div>

        <div class="switch-link">
            Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký ngay</a>
        </div>
    </div>

    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🙈';
            } else {
                input.type = 'password';
                btn.textContent = '👁️';
            }
        }

        // Loading state khi submit
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('loginBtn');
            btn.textContent = 'Đang đăng nhập...';
            btn.style.opacity = '.7';
            btn.disabled = true;
        });
    </script>
</body>
</html>
