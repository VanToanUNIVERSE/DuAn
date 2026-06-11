@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="stat-grid">
    <div class="stat-card pink">
        <div class="stat-label">Tổng môn học</div>
        <div class="stat-value">{{ $stats['total_subjects'] }}</div>
        <div class="stat-icon">📚</div>
    </div>
    <div class="stat-card teal">
        <div class="stat-label">Tổng tín chỉ</div>
        <div class="stat-value">{{ number_format($stats['total_credits']) }}</div>
        <div class="stat-icon">🎓</div>
    </div>
    <div class="stat-card ochre">
        <div class="stat-label">Skill Groups</div>
        <div class="stat-value">{{ $stats['total_skill_groups'] }}</div>
        <div class="stat-icon">🎯</div>
    </div>
    <div class="stat-card mint">
        <div class="stat-label">Program Groups</div>
        <div class="stat-value">{{ $stats['total_program_groups'] }}</div>
        <div class="stat-icon">🗂️</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">

    {{-- Program Group Distribution --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">🗂️ Phân bổ theo Program Group</div>
        </div>
        <div class="card-body" style="padding: 0;">
            <table>
                <thead>
                    <tr>
                        <th>Nhóm chương trình</th>
                        <th style="text-align:right;">Số môn</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($programGroupStats as $pg)
                    <tr>
                        <td><span class="badge badge-teal">{{ $pg->name }}</span></td>
                        <td style="text-align:right; font-weight:700;">{{ $pg->subjects_count }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" style="color:var(--muted); text-align:center; padding:20px;">Chưa có dữ liệu</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Skill Group Distribution --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">🎯 Phân bổ theo Skill Group</div>
        </div>
        <div class="card-body" style="padding: 0;">
            <table>
                <thead>
                    <tr>
                        <th>Nhóm kỹ năng</th>
                        <th style="text-align:right;">Số môn</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($skillGroupStats as $sg)
                    <tr>
                        <td><span class="badge badge-pink">{{ $sg->name }}</span></td>
                        <td style="text-align:right; font-weight:700;">{{ $sg->subjects_count }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" style="color:var(--muted); text-align:center; padding:20px;">Chưa có dữ liệu</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Recent Subjects --}}
<div class="card">
    <div class="card-header">
        <div class="card-title">📋 Môn học mới nhất</div>
        <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary btn-sm">Xem tất cả →</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Mã môn</th>
                        <th>Tên môn học</th>
                        <th>Tín chỉ</th>
                        <th>Skill Group</th>
                        <th>Program Group</th>
                        <th>Thêm lúc</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSubjects as $s)
                    <tr>
                        <td><code style="font-size:12px; background:var(--surface-strong); padding:2px 6px;">{{ $s->subject_code ?? '—' }}</code></td>
                        <td style="font-weight:600;">{{ $s->name }}</td>
                        <td>{{ $s->credits ?? '—' }}</td>
                        <td>@if($s->skillGroup)<span class="badge badge-pink">{{ $s->skillGroup->name }}</span>@else<span style="color:var(--muted);">—</span>@endif</td>
                        <td>@if($s->programGroup)<span class="badge badge-teal">{{ $s->programGroup->name }}</span>@else<span style="color:var(--muted);">—</span>@endif</td>
                        <td style="color:var(--muted); font-size:12px;">{{ $s->created_at->format('d/m/Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="color:var(--muted); text-align:center; padding:30px;">Chưa có môn học nào</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Quick Actions --}}
<div style="display:flex; gap:12px; flex-wrap:wrap;">
    <a href="{{ route('admin.subjects.create') }}" class="btn btn-primary">➕ Thêm môn học mới</a>
    <a href="{{ route('admin.subjects.import.form') }}" class="btn btn-secondary">📥 Import từ Excel/CSV</a>
    <a href="{{ route('admin.skill-groups.index') }}" class="btn btn-secondary">🎯 Quản lý Skill Groups</a>
    <a href="{{ route('admin.program-groups.index') }}" class="btn btn-secondary">🗂️ Quản lý Program Groups</a>
</div>
@endsection
