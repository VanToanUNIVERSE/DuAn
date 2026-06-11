<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramGroup;
use Illuminate\Http\Request;

class ProgramGroupController extends Controller
{
    public function index()
    {
        $programGroups = ProgramGroup::withCount('subjects')->orderBy('name')->get();
        return view('admin.program-groups.index', compact('programGroups'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:program_groups,name']);
        ProgramGroup::create(['name' => $request->name]);
        return back()->with('success', 'Thêm Program Group thành công!');
    }

    public function update(Request $request, ProgramGroup $programGroup)
    {
        $request->validate(['name' => 'required|string|max:255|unique:program_groups,name,' . $programGroup->id]);
        $programGroup->update(['name' => $request->name]);
        return back()->with('success', 'Cập nhật thành công!');
    }

    public function destroy(ProgramGroup $programGroup)
    {
        if ($programGroup->subjects()->count() > 0) {
            return back()->with('error', 'Không thể xóa vì có môn học đang thuộc nhóm này.');
        }
        $programGroup->delete();
        return back()->with('success', 'Đã xóa Program Group.');
    }
}
