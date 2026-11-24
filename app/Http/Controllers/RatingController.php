<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\Teacher;
use App\Models\User;
use App\Services\MetricsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public const KRITERIA = [
        'Ketepatan mengisi kehadiran',
        'Kualitas info kegiatan',
        'Komunikasi individu ke orang tua',
        'Ketertiban proses penjemputan',
    ];

    /**
     * Tab Penilaian — metrik otomatis + bintang (paritas pimNilai()).
     */
    public function index(): View
    {
        $teachers = Teacher::with(['user', 'class'])->orderBy('id')->get()
            ->map(function ($teacher) {
                return (object) [
                    'user' => $teacher->user,
                    'class' => $teacher->class,
                    'metrics' => MetricsService::forClass($teacher->class),
                    'rating' => Rating::where('teacher_id', $teacher->user_id)->first(),
                ];
            });

        return view('pimpinan.penilaian', ['teachers' => $teachers, 'kriteria' => self::KRITERIA]);
    }

    /**
     * Simpan penilaian pimpinan (paritas saveRate — 4 kriteria wajib).
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'guru', 404);

        $data = $request->validate([
            'scores' => ['required', 'array', 'size:4'],
            'scores.*' => ['required', 'integer', 'min:1', 'max:5'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        Rating::updateOrCreate(
            ['teacher_id' => $user->id],
            [
                'leader_id' => $request->user()->id,
                'scores' => array_map(intval(...), array_values($data['scores'])),
                'note' => $data['note'] ?? null,
            ],
        );

        return back()->with('toast', 'Penilaian '.$user->name.' tersimpan');
    }
}
