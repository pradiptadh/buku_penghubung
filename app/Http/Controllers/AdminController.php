<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Daftar semua akun per peran — paritas adminView().
     */
    public function index(): View
    {
        $pimpinan = User::where('role', 'pimpinan')->orderBy('id')->get();
        $guru = User::where('role', 'guru')->with('teacher.class')->orderBy('id')->get();
        $ortuPerKelas = ClassRoom::with(['students.parentUser' => fn ($q) => $q->orderBy('id')])
            ->orderBy('id')->get();

        return view('admin.akun', compact('pimpinan', 'guru', 'ortuPerKelas'));
    }

    /**
     * Reset password akun ke bawaan peran (paritas pwReset).
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $default = User::defaultPasswordFor($user->role);
        $user->update([
            'password' => Hash::make($default),
            'using_default_password' => true,
        ]);

        return back()->with('toast', 'Password '.$user->name.' kembali ke bawaan: '.$default);
    }
}
