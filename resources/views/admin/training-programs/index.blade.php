@extends('admin.layouts.app')

@section('title', 'Chương trình đào tạo')
@section('page-title', 'Quản lý Chương trình đào tạo')
@section('breadcrumb')
 / <span>Chương trình đào tạo</span>
@endsection

@section('content')
<div style="display:grid; grid-template-columns:2fr 1fr; gap:24px; align-items:start;">

    {{-- Danh sách --}}
    <div class="card" style="grid-column: 1;">
        <div class="card-header">
            <div class="card-title">🏫 Danh sách Chương trình đào tạo</div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tên CTĐT</th>
                        <th>Mã CTĐT</th>
                        <th>Bậc & Hệ</th>
                        <th style="text-align:center;">Thời gian</th>
                        <th style="text-align:center;">Niên khóa</th>
                        <th style="text-align:center; width:100px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trainingPrograms as $program)
                    <tr>
                        <td style="font-weight:600;">
                            <span class="badge badge-teal" style="margin-right:6px;">{{ $loop->iteration }}</span>
                            {{ $program->program_name }}
                        </td>
                        <td><span class="badge badge-muted">{{ $program->program_code }}</span></td>
                        <td>
                            <div style="font-size:12px; font-weight:600; color:var(--body-strong);">{{ $program->education_level }}</div>
                            <div style="font-size:11px; color:var(--muted);">{{ $program->program_type }}</div>
                        </td>
                        <td style="text-align:center;">{{ (float)$program->program_duration }} năm</td>
                        <td style="text-align:center;">
                            <span class="badge badge-ochre">{{ $program->academic_year }}</span>
                        </td>
                        <td>
                            <div style="display:flex; gap:4px; justify-content:center;">
                                <button class="btn btn-secondary btn-sm"
                                    onclick="openEditModal({{ $program->id }}, '{{ addslashes($program->program_name) }}', '{{ addslashes($program->program_code) }}', '{{ addslashes($program->education_level) }}', '{{ addslashes($program->program_type) }}', '{{ $program->program_duration }}', '{{ addslashes($program->academic_year) }}')">✏️</button>
                                <form method="POST" action="{{ route('admin.training-programs.destroy', $program) }}"
                                      onsubmit="return confirm('Xóa chương trình đào tạo này? Mọi khung chương trình liên kết cũng có thể bị ảnh hưởng.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:30px; color:var(--muted);">
                            Chưa có Chương trình đào tạo nào
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Form thêm & Sửa --}}
    <div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">➕ Thêm Chương trình đào tạo</div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.training-programs.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Tên Chương trình</label>
                        <input type="text" name="program_name" value="{{ old('program_name') }}" placeholder="VD: Kỹ thuật phần mềm" required>
                        @error('program_name')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Mã CTĐT</label>
                            <input type="text" name="program_code" value="{{ old('program_code') }}" placeholder="VD: KTPM-2022" required>
                            @error('program_code')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label>Niên khóa</label>
                            <input type="text" name="academic_year" value="{{ old('academic_year') }}" placeholder="VD: 2022-2026" required>
                            @error('academic_year')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Bậc đào tạo</label>
                            <input type="text" name="education_level" value="{{ old('education_level', 'Đại học') }}" required>
                            @error('education_level')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label>Hệ đào tạo</label>
                            <input type="text" name="program_type" value="{{ old('program_type', 'Chính quy') }}" required>
                            @error('program_type')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Thời gian đào tạo (Năm)</label>
                        <input type="number" step="0.5" min="0.5" max="10" name="program_duration" value="{{ old('program_duration', '4') }}" required>
                        @error('program_duration')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">💾 Thêm mới</button>
                </form>
            </div>
        </div>

        <div id="edit-modal" class="card" style="display:none; margin-top:16px;">
            <div class="card-header">
                <div class="card-title">✏️ Sửa Chương trình đào tạo</div>
            </div>
            <div class="card-body">
                <form id="edit-form" method="POST">
                    @csrf @method('PUT')
                    <div class="form-group">
                        <label>Tên Chương trình</label>
                        <input type="text" id="edit-name" name="program_name" required>
                    </div>
                    
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Mã CTĐT</label>
                            <input type="text" id="edit-code" name="program_code" required>
                        </div>
                        <div class="form-group">
                            <label>Niên khóa</label>
                            <input type="text" id="edit-year" name="academic_year" required>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Bậc đào tạo</label>
                            <input type="text" id="edit-level" name="education_level" required>
                        </div>
                        <div class="form-group">
                            <label>Hệ đào tạo</label>
                            <input type="text" id="edit-type" name="program_type" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Thời gian đào tạo (Năm)</label>
                        <input type="number" step="0.5" min="0.5" max="10" id="edit-duration" name="program_duration" required>
                    </div>

                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">💾 Lưu</button>
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()" style="flex:1; justify-content:center;">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function openEditModal(id, name, code, level, type, duration, year) {
    document.getElementById('edit-modal').style.display = 'block';
    
    document.getElementById('edit-form').action = '/admin/training-programs/' + id;
    
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-code').value = code;
    document.getElementById('edit-level').value = level;
    document.getElementById('edit-type').value = type;
    document.getElementById('edit-duration').value = duration;
    document.getElementById('edit-year').value = year;
    
    window.scrollTo({ top: document.getElementById('edit-modal').offsetTop, behavior: 'smooth' });
}

function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
}
</script>
@endpush
@endsection
