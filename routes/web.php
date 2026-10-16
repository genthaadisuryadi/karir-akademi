<?php

use App\Http\Controllers\CertificateController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProgramController::class, 'index'])->name('home');

// Detail kelas (judul, deskripsi, pengajar)
Route::get('/pelatihan/{program}', [ProgramController::class, 'show'])->name('program.show');

// Pendaftaran kelas pelatihan
Route::get('/pelatihan/{program}/daftar', [RegistrationController::class, 'create'])->name('registration.create');
Route::post('/pelatihan/{program}/daftar', [RegistrationController::class, 'store'])->name('registration.store');
Route::get('/pendaftaran/berhasil/{code}', [RegistrationController::class, 'success'])->name('registration.success');

// Cek kelas yang sudah didaftar (peserta)
Route::get('/kelas-saya/cari', [RegistrationController::class, 'search'])->name('registration.search');
Route::post('/kelas-saya/cari', [RegistrationController::class, 'find'])->middleware('throttle:10,1')->name('registration.find');
Route::get('/kelas-saya', [RegistrationController::class, 'myClass'])->name('registration.my-class');

// Halaman menu navigasi
Route::get('/langganan', [PageController::class, 'subscription'])->name('subscription');
// PDF sertifikat (alamat yang dimuat di QR Code): /sertifikat/bikin_ai_agent-083823051482
Route::get('/sertifikat/{number}', [CertificateController::class, 'show'])->name('certificate.show');
Route::get('/unduh-sertifikat', [PageController::class, 'certificate'])->name('certificate');
Route::post('/unduh-sertifikat', [PageController::class, 'certificateLookup'])->name('certificate.lookup');
Route::get('/kerjasama-sekolah', [PageController::class, 'partnership'])->name('partnership');

/*
|--------------------------------------------------------------------------
| Panel Admin
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Admin\RegistrationController as AdminRegistrationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;

Route::get('/admin/login', [LoginController::class, 'show'])->name('login');
Route::post('/admin/login', [LoginController::class, 'login'])->middleware('throttle:6,1')->name('login.attempt');
Route::post('/admin/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Kelola kelas
    Route::resource('kelas', AdminProgramController::class)->parameters(['kelas' => 'id'])->except('show');

    // Kelola pendaftar
    Route::get('pendaftar/export', [AdminRegistrationController::class, 'export'])->name('pendaftar.export');
    Route::get('pendaftar', [AdminRegistrationController::class, 'index'])->name('pendaftar.index');
    Route::get('pendaftar/{id}', [AdminRegistrationController::class, 'show'])->whereNumber('id')->name('pendaftar.show');
    Route::patch('pendaftar/{id}/status', [AdminRegistrationController::class, 'updateStatus'])->whereNumber('id')->name('pendaftar.status');
    Route::delete('pendaftar/{id}', [AdminRegistrationController::class, 'destroy'])->whereNumber('id')->name('pendaftar.destroy');

    // Kelola pengguna (khusus admin)
    Route::middleware('admin')->group(function () {
        Route::resource('pengguna', AdminUserController::class)->parameters(['pengguna' => 'id'])->except('show');
    });
});
