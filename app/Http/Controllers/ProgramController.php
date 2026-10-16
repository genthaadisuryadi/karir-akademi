<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Registration;

class ProgramController extends Controller
{
    public function index()
    {
        $programs = Program::where('is_active', true)->orderBy('sort_order')->get();

        return view('home', compact('programs'));
    }

    /** Detail kelas: poster, deskripsi (Markdown), benefit, JP, pengajar, dan tautan grup WhatsApp. */
    public function show(Program $program)
    {
        abort_unless($program->is_active, 404);

        // Tautan grup WhatsApp tampil begitu pengunjung sudah mendaftar (apa pun statusnya).
        $isRegistered = Registration::sessionHasRegistered($program);

        return view('program.show', compact('program', 'isRegistered'));
    }
}
