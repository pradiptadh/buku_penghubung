<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\OrtuController;
use App\Http\Controllers\PickupController;
use App\Http\Controllers\PimpinanController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SafetyController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StudentController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): RedirectResponse {
    return auth()->guest() ? redirect()->route('login') : redirect()->route(auth()->user()->homeRoute());
})->name('home');

/* ---------- AUTH (tanpa registrasi: akun dibuat admin/seeder) ---------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/password', [LoginController::class, 'editPassword'])->name('password.edit');
    Route::post('/password', [LoginController::class, 'updatePassword'])->name('password.update');
});

/* ---------- GURU ---------- */
Route::middleware(['auth', 'role:guru'])->prefix('guru')->group(function () {
    Route::get('/kehadiran', [AttendanceController::class, 'index'])->name('guru.kehadiran');
    Route::post('/kehadiran/semua', [AttendanceController::class, 'markAll'])->name('guru.attendance.all');
    Route::post('/kehadiran/{student}', [AttendanceController::class, 'toggle'])->name('guru.attendance.toggle');

    Route::get('/info', [PostController::class, 'indexForTeacher'])->name('guru.info');
    Route::get('/info/buat', [PostController::class, 'create'])->name('guru.info.create');
    Route::post('/info', [PostController::class, 'store'])->name('guru.info.store');

    Route::get('/jemput', [PickupController::class, 'indexForTeacher'])->name('guru.jemput');
    Route::post('/jemput/remind-setting', [PickupController::class, 'saveRemindSetting'])->name('guru.jemput.remind');
    Route::post('/jemput/semua-siap', [PickupController::class, 'readyAll'])->name('guru.jemput.all');
    Route::post('/jemput/{student}/siap', [PickupController::class, 'ready'])->name('guru.jemput.ready');
    Route::post('/jemput/{student}/dijemput', [PickupController::class, 'picked'])->name('guru.jemput.picked');
    Route::post('/jemput/{student}', [PickupController::class, 'update'])->name('guru.jemput.update');

    Route::get('/jadwal', [ScheduleController::class, 'indexForTeacher'])->name('guru.jadwal');
    Route::post('/jadwal/{day}', [ScheduleController::class, 'store'])->whereNumber('day')->name('guru.jadwal.store');
});

/* ---------- ORANG TUA ---------- */
Route::middleware(['auth', 'role:ortu'])->prefix('ortu')->group(function () {
    Route::get('/', [OrtuController::class, 'beranda'])->name('ortu.beranda');
    Route::get('/info', [OrtuController::class, 'info'])->name('ortu.info');
    Route::get('/kehadiran', [OrtuController::class, 'hadir'])->name('ortu.hadir');
    Route::post('/lapor-tidak-masuk', [OrtuController::class, 'absence'])->name('ortu.absence');
    Route::post('/tanda-tangan/{post}', [PostController::class, 'sign'])->name('ortu.sign');
    Route::post('/pickup/otw', [PickupController::class, 'otw'])->name('ortu.otw');
    Route::post('/safety/ack', [SafetyController::class, 'ack'])->name('ortu.safety.ack');
    Route::post('/safety/darurat', [SafetyController::class, 'sos'])->name('ortu.safety.sos');
    Route::post('/safety/senyap', [SafetyController::class, 'mute'])->name('ortu.safety.mute');
    Route::get('/jadwal', [ScheduleController::class, 'showForParent'])->name('ortu.jadwal');
});

/* ---------- PIMPINAN ---------- */
Route::middleware(['auth', 'role:pimpinan'])->prefix('pimpinan')->group(function () {
    Route::get('/', [PimpinanController::class, 'ringkasan'])->name('pimpinan.ringkasan');
    Route::get('/aktivitas', [PimpinanController::class, 'aktivitas'])->name('pimpinan.aktivitas');
    Route::get('/penilaian', [RatingController::class, 'index'])->name('pimpinan.penilaian');
    Route::post('/penilaian/{user}', [RatingController::class, 'store'])->name('pimpinan.penilaian.store');
    Route::get('/data-murid', [StudentController::class, 'index'])->name('pimpinan.data');
    Route::post('/data-murid', [StudentController::class, 'store'])->name('pimpinan.data.store');
    Route::post('/data-murid/{student}', [StudentController::class, 'update'])->name('pimpinan.data.update');
});

/* ---------- ADMIN ---------- */
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.akun');
    Route::post('/reset-password/{user}', [AdminController::class, 'resetPassword'])->name('admin.reset');
});

/* ---------- POLLING (badge, counter, alarm — paritas tick()) ---------- */
Route::middleware('auth')->get('/poll', [PollController::class, 'state'])->name('poll');
