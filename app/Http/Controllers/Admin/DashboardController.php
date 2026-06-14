<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\SkillGroup;
use App\Models\ProgramGroup;
use App\Models\UserGrade;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_subjects'       => Subject::count(),
            'total_credits'        => Subject::sum('credits') ?? 0,
            'total_skill_groups'   => SkillGroup::count(),
            'total_program_groups' => ProgramGroup::count(),
        ];

        // Phân bổ theo program group
        $programGroupStats = ProgramGroup::withCount('subjects')->get();

        // Phân bổ theo skill group
        $skillGroupStats = SkillGroup::withCount('subjects')->get();

        // Môn học gần đây thêm vào
        $recentSubjects = Subject::with(['skillGroup', 'programGroup'])
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'programGroupStats',
            'skillGroupStats',
            'recentSubjects'
        ));
    }
}
