<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Registration;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'programs' => Program::count(),
            'active_programs' => Program::where('is_active', true)->count(),
            'registrations' => Registration::count(),
            'issued' => Registration::where('status', 'certificate_issued')->count(),
            'users' => User::count(),
        ];

        $recent = Registration::with('program')->latest()->take(6)->get();
        $programs = Program::withCount('registrations')->orderBy('sort_order')->get();

        return view('admin.dashboard', compact('stats', 'recent', 'programs'));
    }
}
