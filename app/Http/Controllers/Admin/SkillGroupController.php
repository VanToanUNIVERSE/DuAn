<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SkillGroup;
use Illuminate\Http\Request;

class SkillGroupController extends Controller
{
    public function index()
    {
        $skillGroups = SkillGroup::withCount('subjects')->orderBy('name')->get();
        return view('admin.skill-groups.index', compact('skillGroups'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:skill_groups,name']);
        SkillGroup::create(['name' => $request->name]);
        return back()->with('success', 'Thêm Skill Group thành công!');
    }

    public function update(Request $request, SkillGroup $skillGroup)
    {
        $request->validate(['name' => 'required|string|max:255|unique:skill_groups,name,' . $skillGroup->id]);
        $skillGroup->update(['name' => $request->name]);
        return back()->with('success', 'Cập nhật thành công!');
    }

    public function destroy(SkillGroup $skillGroup)
    {
        if ($skillGroup->subjects()->count() > 0) {
            return back()->with('error', 'Không thể xóa vì có môn học đang thuộc nhóm này.');
        }
        $skillGroup->delete();
        return back()->with('success', 'Đã xóa Skill Group.');
    }
}
