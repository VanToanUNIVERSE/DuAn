<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký – Hệ thống Kế hoạch Học tập</title>
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
            background: radial-gradient(circle, #56d38a, transparent);
            top: -150px; right: -100px;
        }
        body::after {
            width: 400px; height: 400px;
            background: radial-gradient(circle, #7c6af7, transparent);
            bottom: -100px; left: -100px;
            animation-delay: -4s;
        }

        @keyframes float {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(25px, 15px) scale(1.05); }
        }

        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2.5rem 2.5rem;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.1);
            position: relative;
            z-index: 1;
            animation: slideUp .5s cubic-bezier(.16,1,.3,1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .logo {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-bottom: 1.75rem;
        }
        .logo-icon {
            width: 46px; height: 46px;
            background: linear-gradient(135deg, #56d38a, #7c6af7);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.35rem;
            box-shadow: 0 4px 20px rgba(86,211,138,.35);
        }
        .logo-text {
            font-size: .85rem;
            font-weight: 600;
            color: var(--text);
            line-height: 1.3;
        }
        .logo-text span { color: var(--text-muted); font-weight: 400; font-size: .78rem; }

        h1 {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -.5px;
            margin-bottom: .3rem;
        }
        .subtitle {
            font-size: .875rem;
            color: var(--text-muted);
            margin-bottom: 1.75rem;
        }

        /* 2-column grid cho form */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0 1rem;
        }
        .form-grid .form-group:first-child,
        .form-group.full { grid-column: 1 / -1; }

        .form-group { margin-bottom: 1rem; }

        label {
            display: block;
            font-size: .82rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: .4rem;
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
        .input-wrap:focus-within .input-icon { color: var(--accent); }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: .82rem 1rem .82rem 2.6rem;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 12px;
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: .875rem;
            transition: border-color .25s, box-shadow .25s, background .25s;
            outline: none;
        }
        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--input-focus);
            background: rgba(255,255,255,0.09);
        }
        input::placeholder { color: rgba(241,241,245,0.3); }

        .field-error {
            font-size: .77rem;
            color: var(--error);
            margin-top: .3rem;
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        /* Password strength bar */
        .pw-strength {
            margin-top: .45rem;
            display: none;
        }
        .pw-strength-bar {
            height: 4px;
            border-radius: 2px;
            background: rgba(255,255,255,.1);
            overflow: hidden;
        }
        .pw-strength-fill {
            height: 100%;
            border-radius: 2px;
            transition: width .3s, background .3s;
            width: 0%;
        }
        .pw-strength-label {
            font-size: .75rem;
            margin-top: .3rem;
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
        .alert-error { background: rgba(255,107,138,.12); border: 1px solid rgba(255,107,138,.3); color: var(--error); }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }

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
            margin-top: .5rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #56d38a, #7c6af7);
            color: #fff;
            box-shadow: 0 4px 20px rgba(86,211,138,.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(86,211,138,.35);
        }
        .btn-primary:active { transform: translateY(0); }

        .divider {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin: 1.25rem 0 1rem;
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

        /* Terms checkbox */
        .terms-row {
            display: flex;
            align-items: flex-start;
            gap: .6rem;
            margin-bottom: .25rem;
        }
        .terms-row input[type="checkbox"] {
            width: 16px; height: 16px;
            margin-top: 2px;
            accent-color: var(--accent);
            flex-shrink: 0;
        }
        .terms-row label {
            font-size: .82rem;
            color: var(--text-muted);
            cursor: pointer;
            margin-bottom: 0;
        }
        .terms-row a { color: var(--accent); text-decoration: none; }
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

        <h1>Tạo tài khoản</h1>
        <p class="subtitle">Điền thông tin để bắt đầu lên kế hoạch học tập</p>

        {{-- Validation errors tổng hợp --}}
        @if($errors->any())
            <div class="alert alert-error">
                <span>⚠️</span>
                <div>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" id="registerForm">
            @csrf

            <div class="form-grid">
                {{-- Họ và tên --}}
                <div class="form-group full">
                    <label for="fullName">Họ và tên</label>
                    <div class="input-wrap">
                        <input
                            id="fullName"
                            type="text"
                            name="fullName"
                            value="{{ old('fullName') }}"
                            placeholder="Nguyễn Văn A"
                            autocomplete="name"
                            required
                        >
                        <span class="input-icon">👤</span>
                    </div>
                    @error('fullName')
                        <div class="field-error">⚠️ {{ $message }}</div>
                    @enderror
                </div>

                {{-- Mã sinh viên --}}
                <div class="form-group">
                    <label for="student_code">Mã sinh viên</label>
                    <div class="input-wrap">
                        <input
                            id="student_code"
                            type="text"
                            name="student_code"
                            value="{{ old('student_code') }}"
                            placeholder="SV2024001"
                            autocomplete="off"
                            required
                        >
                        <span class="input-icon">🪪</span>
                    </div>
                    @error('student_code')
                        <div class="field-error">⚠️ {{ $message }}</div>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrap">
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="sv@email.com"
                            autocomplete="email"
                            required
                        >
                        <span class="input-icon">📧</span>
                    </div>
                    @error('email')
                        <div class="field-error">⚠️ {{ $message }}</div>
                    @enderror
                </div>

                {{-- Mật khẩu --}}
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <div class="input-wrap">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Tối thiểu 8 ký tự"
                            autocomplete="new-password"
                            oninput="checkStrength(this.value)"
                            required
                        >
                        <span class="input-icon">🔒</span>
                        <button type="button" class="toggle-pw" onclick="togglePassword('password', this)">👁️</button>
                    </div>
                    <div class="pw-strength" id="pwStrength">
                        <div class="pw-strength-bar">
                            <div class="pw-strength-fill" id="pwFill"></div>
                        </div>
                        <div class="pw-strength-label" id="pwLabel"></div>
                    </div>
                    @error('password')
                        <div class="field-error">⚠️ {{ $message }}</div>
                    @enderror
                </div>

                {{-- Xác nhận mật khẩu --}}
                <div class="form-group">
                    <label for="password_confirmation">Xác nhận mật khẩu</label>
                    <div class="input-wrap">
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            placeholder="Nhập lại mật khẩu"
                            autocomplete="new-password"
                            oninput="checkConfirm()"
                            required
                        >
                        <span class="input-icon">🔐</span>
                        <button type="button" class="toggle-pw" onclick="togglePassword('password_confirmation', this)">👁️</button>
                    </div>
                    <div class="field-error" id="confirmError" style="display:none;">⚠️ Mật khẩu không khớp</div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="registerBtn">
                ✨ Tạo tài khoản
            </button>
        </form>

        <div class="divider">đã có tài khoản?</div>

        <div class="switch-link">
            <a href="{{ route('login') }}">← Quay lại đăng nhập</a>
        </div>
    </div>

    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            input.type = input.type === 'password' ? 'text' : 'password';
            btn.textContent = input.type === 'password' ? '👁️' : '🙈';
        }

        function checkStrength(val) {
            const bar = document.getElementById('pwFill');
            const lbl = document.getElementById('pwLabel');
            const wrap = document.getElementById('pwStrength');

            if (!val) { wrap.style.display = 'none'; return; }
            wrap.style.display = 'block';

            let score = 0;
            if (val.length >= 8)  score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const levels = [
                { pct: '25%', color: '#ff6b8a', text: '😟 Quá yếu' },
                { pct: '50%', color: '#ffa94d', text: '😐 Trung bình' },
                { pct: '75%', color: '#ffd43b', text: '🙂 Khá' },
                { pct: '100%',color: '#56d38a', text: '💪 Mạnh' },
            ];
            const lvl = levels[Math.max(score - 1, 0)];
            bar.style.width = lvl.pct;
            bar.style.background = lvl.color;
            lbl.textContent = lvl.text;
            lbl.style.color = lvl.color;
        }

        function checkConfirm() {
            const pw  = document.getElementById('password').value;
            const cpw = document.getElementById('password_confirmation').value;
            const err = document.getElementById('confirmError');
            err.style.display = (cpw && pw !== cpw) ? 'flex' : 'none';
        }

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const pw  = document.getElementById('password').value;
            const cpw = document.getElementById('password_confirmation').value;
            if (pw !== cpw) { e.preventDefault(); return; }

            const btn = document.getElementById('registerBtn');
            btn.textContent = 'Đang tạo tài khoản...';
            btn.style.opacity = '.7';
            btn.disabled = true;
        });
    </script>
</body>
</html>
