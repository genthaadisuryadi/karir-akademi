<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Registration;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function subscription()
    {
        return view('pages.langganan');
    }

    public function partnership()
    {
        return view('pages.kerjasama');
    }

    public function certificate()
    {
        return view('pages.sertifikat');
    }

    public function certificateLookup(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:50']], [
            'code.required' => 'Kolom pencarian wajib diisi.',
        ]);

        $input = trim($data['code']);

        // 1. CEK PEMATERI: Jika input adalah nomor WA pemateri, langsung buka PDF-nya
        $programAsInstructor = Program::where('instructor_phone', $input)->first();
        if ($programAsInstructor) {
            return redirect('/sertifikat/' . $programAsInstructor->certificate_prefix . '-' . $programAsInstructor->instructor_phone);
        }

        // 2. CEK PESERTA: Jika bukan pemateri, cari data pendaftar (peserta)
        $registration = Registration::with('program')
            ->where('code', strtoupper($input))
            ->orWhere('phone', $input)
            ->orWhere('email', $input)
            ->first();

        return view('pages.sertifikat', ['registration' => $registration, 'searched' => true]);
    }
}