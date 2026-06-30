<div class="drawer-overlay" id="plan-history-overlay" onclick="closePlanHistoryDrawer()"></div>
<div class="history-drawer" id="plan-history-drawer">
    <div class="drawer-header">
        <div>
            <div class="drawer-title">🕘 Lịch sử thay đổi kế hoạch</div>
            <div class="drawer-subtitle">Các lần kế hoạch được điều chỉnh lớn</div>
        </div>
        <button class="drawer-close" onclick="closePlanHistoryDrawer()">✕</button>
    </div>
    <div class="drawer-body" id="plan-history-body">
        <div class="history-empty" id="plan-history-empty">
            <span class="history-empty-icon">🗂️</span>
            <p>Chưa có thay đổi nào được ghi lại.<br>Lịch sử xuất hiện khi bạn áp dụng tư vấn, đổi mục tiêu, tạo lại kế hoạch hoặc khi xử lý rớt môn.</p>
        </div>
        <div id="plan-history-list"></div>
    </div>
</div>
