@extends('admin.layouts.app')

@section('title', 'Quản lý môn học')
@section('page-title', 'Quản lý môn học')
@section('breadcrumb')
 / <span>Môn học</span>
@endsection

@section('content')

{{-- Filter Bar --}}
<form method="GET" action="{{ route('admin.subjects.index') }}">
<div class="filter-bar">
    <div class="form-group" style="flex:2; min-width:220px;">
        <label>Tìm kiếm</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Mã môn, tên môn học...">
    </div>
    <div class="form-group">
        <label>Program Group</label>
        <select name="program_group_id">
            <option value="">Tất cả</option>
            @foreach($programGroups as $pg)
                <option value="{{ $pg->id }}" {{ request('program_group_id') == $pg->id ? 'selected' : '' }}>{{ $pg->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Skill Group</label>
        <select name="skill_group_id">
            <option value="">Tất cả</option>
            @foreach($skillGroups as $sg)
                <option value="{{ $sg->id }}" {{ request('skill_group_id') == $sg->id ? 'selected' : '' }}>{{ $sg->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group" style="min-width:140px;">
        <label>Loại môn</label>
        <select name="is_elective">
            <option value="">Tất cả</option>
            <option value="0" {{ request('is_elective') === '0' ? 'selected' : '' }}>✔ Bắt buộc</option>
            <option value="1" {{ request('is_elective') === '1' ? 'selected' : '' }}>📝 Tự chọn</option>
        </select>
    </div>
    <div style="display:flex; gap:8px; align-items:flex-end;">
        <button type="submit" class="btn btn-primary">🔍 Lọc</button>
        <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary">✕ Reset</a>
    </div>
</div>
</form>

{{-- Actions --}}
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
    <div style="font-size:13px; color:var(--muted);">
        Hiển thị <strong>{{ $subjects->firstItem() ?? 0 }}–{{ $subjects->lastItem() ?? 0 }}</strong>
        / tổng <strong>{{ $subjects->total() }}</strong> môn học
    </div>
    <div style="display:flex; gap:8px; align-items:center;">
        <form action="{{ route('admin.subjects.deleteAll') }}" method="POST" onsubmit="return confirm('CẢNH BÁO: Bạn có chắc chắn muốn XÓA TOÀN BỘ môn học trong hệ thống không? Hành động này KHÔNG THỂ HOÀN TÁC!');" style="margin:0;">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm" style="background-color: #dc2626; color: white; border: none; font-weight: 500; padding: 4px 10px; cursor: pointer;">🗑 Xóa tất cả</button>
        </form>
        <a href="{{ route('admin.subjects.import.form') }}" class="btn btn-secondary btn-sm">📥 Import Excel/CSV</a>
        <a href="{{ route('admin.subjects.create') }}" class="btn btn-primary btn-sm">➕ Thêm môn học</a>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th width="110">Mã môn</th>
                    <th>Tên môn học</th>
                    <th width="70" style="text-align:center;">TC</th>
                    <th width="110" style="text-align:center;">Loại</th>
                    <th width="140">Skill Group</th>
                    <th width="140">Program Group</th>
                    <th width="150">Yêu cầu</th>
                    <th width="120" style="text-align:center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subjects as $subject)
                <tr>
                    <td>
                        @if($subject->subject_code)
                            <code style="font-size:11px; background:var(--surface-strong); padding:2px 6px; border:1px solid var(--hairline);">
                                {{ $subject->subject_code }}
                            </code>
                        @else
                            <span style="color:var(--muted-soft);">—</span>
                        @endif
                    </td>
                    <td style="font-weight:600; color:var(--ink);">{{ $subject->name }}</td>
                    <td style="text-align:center; font-weight:700;">{{ $subject->credits ?? '—' }}</td>
                    <td style="text-align:center;">
                        @if($subject->is_elective)
                            <span class="badge" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;font-size:11px;">📝 Tự chọn</span>
                        @else
                            <span class="badge" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;font-size:11px;">✔ Bắt buộc</span>
                        @endif
                    </td>
                    <td>
                        @if($subject->skillGroup)
                            <span class="badge badge-pink">{{ $subject->skillGroup->name }}</span>
                        @else <span style="color:var(--muted-soft);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($subject->programGroup)
                            <span class="badge badge-teal">{{ $subject->programGroup->name }}</span>
                        @else <span style="color:var(--muted-soft);">—</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $reqColors = [
                                'none'            => 'badge-muted',
                                'completed_basic' => 'badge-mint',
                                'completed_major' => 'badge-teal',
                                'completed_all'   => 'badge-ochre',
                                'min_credits'     => 'badge-pink',
                            ];
                            $reqType = $subject->requirement_type ?? 'none';
                        @endphp
                        <span class="badge {{ $reqColors[$reqType] ?? 'badge-muted' }}" style="font-size:10px;">
                            {{ App\Models\Subject::REQUIREMENT_TYPES[$reqType] ?? $reqType }}
                        </span>
                    </td>
                    <td>
                        <div style="display:flex; gap:4px; justify-content:center;">
                            <a href="{{ route('admin.subjects.edit', $subject) }}" class="btn btn-secondary btn-sm" title="Sửa">✏️</a>
                            <form method="POST" action="{{ route('admin.subjects.destroy', $subject) }}"
                                  onsubmit="return confirm('Xóa môn học này?');" style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Xóa">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center; padding:40px; color:var(--muted);">
                        📭 Không tìm thấy môn học nào
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pagination --}}
<div class="pagination">
    {{ $subjects->links() }}
</div>

@endsection
