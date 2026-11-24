<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    /**
     * Tab Data murid — paritas pimData().
     */
    public function index(Request $request): View
    {
        $classes = ClassRoom::with(['students.parentUser', 'teachers.user'])->orderBy('id')->get();

        return view('pimpinan.data-murid', [
            'classes' => $classes,
            'editId' => $request->query('edit'),
            'newClass' => $request->query('new'),
        ]);
    }

    /**
     * Daftarkan murid baru + akun ortu otomatis (password ortu123).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'name' => ['required', 'string', 'max:40'],
            'parent' => ['required', 'string', 'max:60'],
            'parent_email' => ['nullable', 'email'],
        ]);

        $parent = User::create([
            'name' => $data['parent'],
            'email' => $this->parentEmail($data['parent'], $data['parent_email'] ?? null),
            'password' => Hash::make(User::defaultPasswordFor('ortu')),
            'role' => 'ortu',
        ]);

        $student = Student::create([
            'name' => $data['name'],
            'class_id' => $data['class_id'],
            'parent_user_id' => $parent->id,
        ]);

        return redirect()->route('pimpinan.data')
            ->with('toast', $student->name.' terdaftar · akun '.$parent->name.' dibuat (password ortu123)');
    }

    /**
     * Edit nama murid & nama orang tua.
     */
    public function update(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'parent' => ['required', 'string', 'max:60'],
        ]);

        $student->update(['name' => $data['name']]);
        $student->parentUser?->update(['name' => $data['parent']]);

        return redirect()->route('pimpinan.data')->with('toast', 'Data '.$data['name'].' diperbarui');
    }

    /**
     * Email unik: pakai input manual atau slug nama ortu + penomoran bila bentrok.
     */
    private function parentEmail(string $parentName, ?string $manual): string
    {
        if ($manual) {
            return $manual;
        }

        $base = Str::slug($parentName, '.').'@sekolah.test';
        $email = $base;
        $i = 2;
        while (User::where('email', $email)->exists()) {
            $email = Str::slug($parentName, '.').$i.'@sekolah.test';
            $i++;
        }

        return $email;
    }
}
