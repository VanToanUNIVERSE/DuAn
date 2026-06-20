@extends('admin.layouts.app')

@section('title', 'Skill Groups')
@section('page-title', 'Quản lý Skill Groups')
@section('breadcrumb')
 / <span>Skill Groups</span>
@endsection

@section('content')
<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; align-items:start;">

    {{-- Danh sách --}}
    <div class="card" style="grid-column: 1;">
        <div class="card-header">
            <div class="card-title">🎯 Danh sách Skill Groups</div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tên nhóm kỹ năng</th>
                        <th>Định hướng</th>
                        <th style="text-align:center; width:90px;">Số môn</th>
                        <th style="text-align:center; width:120px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($skillGroups as $sg)
                    @php
                        $focusLabels = \App\Models\SkillGroup::FOCUS_AREAS;
                    @endphp
                    <tr>
                        <td style="font-weight:600;">
                            <span class="badge badge-pink" style="margin-right:6px;">{{ $loop->iteration }}</span>
                            {{ $sg->name }}
                        </td>
                        <td>
                            @if($sg->focus_area)
                                <span class="badge badge-muted">{{ $focusLabels[$sg->focus_area] ?? $sg->focus_area }}</span>
                            @else
                                <span style="color:var(--muted); font-size:0.8rem;">—</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <span class="badge badge-muted">{{ $sg->subjects_count }} môn</span>
                        </td>
                        <td>
                            <div style="display:flex; gap:4px; justify-content:center;">
                                <button class="btn btn-secondary btn-sm"
                                    onclick="openEditModal({{ $sg->id }}, '{{ addslashes($sg->name) }}', '{{ $sg->focus_area ?? '' }}')">✏️</button>
                                <form method="POST" action="{{ route('admin.skill-groups.destroy', $sg) }}"
                                      onsubmit="return confirm('Xóa skill group này?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="text-align:center; padding:30px; color:var(--muted);">
                            Chưa có Skill Group nào
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Form thêm --}}
    <div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">➕ Thêm Skill Group</div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.skill-groups.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Tên Skill Group</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="VD: Kỹ thuật phần mềm" required>
                        @error('name')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Định hướng kỹ năng</label>
                        <select name="focus_area" class="form-control">
                            <option value="">— Không phân loại —</option>
                            @foreach(\App\Models\SkillGroup::FOCUS_AREAS as $key => $label)
                                <option value="{{ $key }}" {{ old('focus_area') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">💾 Thêm mới</button>
                </form>
            </div>
        </div>

        {{-- Edit Modal (inline) --}}
        <div id="edit-modal" class="card" style="display:none;">
            <div class="card-header">
                <div class="card-title">✏️ Sửa Skill Group</div>
            </div>
            <div class="card-body">
                <form id="edit-form" method="POST">
                    @csrf @method('PUT')
                    <div class="form-group">
                        <label>Tên mới</label>
                        <input type="text" id="edit-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Định hướng kỹ năng</label>
                        <select id="edit-focus" name="focus_area" class="form-control">
                            <option value="">— Không phân loại —</option>
                            @foreach(\App\Models\SkillGroup::FOCUS_AREAS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="btn btn-primary">💾 Lưu</button>
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function openEditModal(id, name, focusArea) {
    document.getElementById('edit-modal').style.display = 'block';
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-focus').value = focusArea || '';
    document.getElementById('edit-form').action = '/admin/skill-groups/' + id;
    document.getElementById('edit-name').focus();
}
function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
}
</script>
@endpush
@endsection
