<div class="drawer-overlay" id="history-drawer-overlay" onclick="closeHistoryDrawer()"></div>
<div class="history-drawer" id="history-drawer">
    <div class="drawer-header">
        <div>
            <div class="drawer-title">📚 Lịch Sử Học Kỳ</div>
            <div class="drawer-subtitle">Các học kỳ bạn đã hoàn tất</div>
        </div>
        <button class="drawer-close" onclick="closeHistoryDrawer()">✕</button>
    </div>
    <div class="drawer-body" id="history-drawer-body">
        <div class="history-empty" id="history-empty">
            <span class="history-empty-icon">📖</span>
            <p>Chưa có học kỳ nào được hoàn tất.<br>Ấn <strong>✓ Hoàn tất học kỳ</strong> sau khi kết thúc mỗi kỳ học.</p>
        </div>
        <div id="history-list"></div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     APP SHELL
══════════════════════════════════════════════════════════════════ --}}
<div class="app-shell">