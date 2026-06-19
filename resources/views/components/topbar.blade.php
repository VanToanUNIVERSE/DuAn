<header class="topbar">
            <div>
                <div class="topbar-title" id="topbar-title">Dashboard</div>
                <div class="topbar-subtitle" id="topbar-subtitle">Tổng quan tiến độ học tập của bạn</div>
            </div>
            <div class="topbar-right" style="display: flex; align-items: center; gap: 16px;">
                <button class="btn-secondary" onclick="toggleHistoryDrawer()" style="height: 36px; font-size: 0.85rem; padding: 0 16px;">📚 Lịch sử học kỳ</button>
                <div style="font-size:0.8rem;color:var(--muted);">{{ Auth::user()->email }}</div>
            </div>
        </header>