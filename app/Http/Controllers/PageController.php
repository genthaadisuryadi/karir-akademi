<?php

namespace App\Http\Controllers;

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
        $data = $request->validate(['code' => ['required', 'string', 'max:30']], [
            'code.required' => 'Kode pendaftaran wajib diisi.',
        ]);

        $registration = Registration::with('program')
            ->where('code', strtoupper(trim($data['code'])))
            ->first();

        return view('pages.sertifikat', ['registration' => $registration, 'searched' => true]);
    }
}
