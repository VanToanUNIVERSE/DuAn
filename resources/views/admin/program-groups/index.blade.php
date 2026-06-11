@extends('admin.layouts.app')

@section('title', 'Program Groups')
@section('page-title', 'Quản lý Program Groups')
@section('breadcrumb')
 / <span>Program Groups</span>
@endsection

@section('content')
<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; align-items:start;">

    {{-- Danh sách --}}
    <div class="card" style="grid-column: 1;">
        <div class="card-header">
            <div class="card-title">🗂️ Danh sách Program Groups</div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tên nhóm chương trình</th>
                        <th style="text-align:center; width:90px;">Số môn</th>
                        <th style="text-align:center; width:120px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($programGroups as $pg)
                    <tr>
                        <td style="font-weight:600;">
                            <span class="badge badge-teal" style="margin-right:6px;">{{ $loop->iteration }}</span>
                            {{ $pg->name }}
                        </td>
                        <td style="text-align:center;">
                            <span class="badge badge-muted">{{ $pg->subjects_count }} môn</span>
                        </td>
                        <td>
                            <div style="display:flex; gap:4px; justify-content:center;">
                                <button class="btn btn-secondary btn-sm"
                                    onclick="openEditModal({{ $pg->id }}, '{{ addslashes($pg->name) }}')">✏️</button>
                                <form method="POST" action="{{ route('admin.program-groups.destroy', $pg) }}"
                                      onsubmit="return confirm('Xóa program group này?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="text-align:center; padding:30px; color:var(--muted);">
                            Chưa có Program Group nào
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
                <div class="card-title">➕ Thêm Program Group</div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.program-groups.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Tên Program Group</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="VD: Đại cương, Chuyên ngành..." required>
                        @error('name')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">💾 Thêm mới</button>
                </form>
            </div>
        </div>

        <div id="edit-modal" class="card" style="display:none; margin-top:16px;">
            <div class="card-header">
                <div class="card-title">✏️ Sửa Program Group</div>
            </div>
            <div class="card-body">
                <form id="edit-form" method="POST">
                    @csrf @method('PUT')
                    <div class="form-group">
                        <label>Tên mới</label>
                        <input type="text" id="edit-name" name="name" required>
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
function openEditModal(id, name) {
    document.getElementById('edit-modal').style.display = 'block';
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-form').action = '/admin/program-groups/' + id;
    document.getElementById('edit-name').focus();
}
function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
}
</script>
@endpush
@endsection
