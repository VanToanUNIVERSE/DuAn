<div class="drawer-overlay" id="grade-drawer-overlay" onclick="closeGradeDrawer()"></div>
<div class="grade-drawer" id="grade-drawer">
    <div class="drawer-header">
        <div>
            <div class="drawer-title">📝 Nhập Điểm Môn Học</div>
            <div class="drawer-subtitle">Điểm > 5.0 được tính là Pass ✅</div>
        </div>
        <button class="drawer-close" onclick="closeGradeDrawer()">✕</button>
    </div>
    <div class="drawer-search">
        <input type="text" id="grade-search" class="clay-input" placeholder="🔍 Tìm kiếm môn học..." oninput="filterGradeSearch(this.value)" style="height:38px;font-size:0.84rem;">
    </div>
    <div class="drawer-stats">
        <div class="drawer-stat pass">✓ Pass: <strong id="drawer-pass-count">0</strong></div>
        <div class="drawer-stat fail">✗ Fail: <strong id="drawer-fail-count">0</strong></div>
        <div class="drawer-stat">Chưa nhập: <strong id="drawer-empty-count">0</strong></div>
    </div>
    <div class="grade-drawer-body" id="grade-drawer-body">
        @foreach($subjects as $semName => $semSubjects)
            <div class="drawer-sem-group">
                <div class="drawer-sem-header">Học kỳ chuẩn {{ $semName }}</div>
                <div class="drawer-subjects-list">
                    @foreach($semSubjects as $sub)
                        <div class="drawer-subject-card" id="lbl-sub-{{ $sub->id }}" data-name="{{ strtolower($sub->name) }}">
                            <div class="drawer-subject-info">
                                <div class="drawer-subject-name">{{ $sub->name }}</div>
                                <div class="drawer-subject-meta">{{ $sub->credits }} tín chỉ · {{ $sub->subjectType?->name }}</div>
                            </div>
                            <div class="drawer-grade-wrap">
                                <input type="number"
                                       class="drawer-grade-input grade-input"
                                       id="grade-{{ $sub->id }}"
                                       data-subject-id="{{ $sub->id }}"
                                       data-credits="{{ $sub->credits }}"
                                       min="0" max="10" step="0.1"
                                       placeholder="—"
                                       oninput="onGradeChange({{ $sub->id }}, this)">
                                <span class="drawer-grade-status empty" id="status-{{ $sub->id }}">—</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>