<?php

namespace App\Http\Controllers;

use App\Models\ScheduleSlot;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ScheduleController extends Controller
{
    /**
     * Tab Jadwal guru — lihat & edit per hari (paritas jadwalView).
     */
    public function indexForTeacher(Request $request): View
    {
        $class = Teacher::with('class.schedule')->where('user_id', auth()->id())->firstOrFail()->class;

        [$day, $slots, $editing] = $this->resolve($request, $class->id);

        return view('guru.jadwal', compact('class', 'day', 'slots', 'editing'));
    }

    /**
     * Simpan jadwal satu hari: array jam[] & kegiatan[] (paritas jSave).
     */
    public function store(Request $request, int $day): RedirectResponse
    {
        $classId = Teacher::where('user_id', auth()->id())->value('class_id');
        abort_unless($classId !== null && $day >= 1 && $day <= 5, 404);

        $data = $request->validate([
            'jam' => ['required', 'array'],
            'jam.*' => ['nullable', 'date_format:H:i'],
            'kegiatan' => ['required', 'array'],
            'kegiatan.*' => ['nullable', 'string', 'max:80'],
        ]);

        $rows = [];
        foreach ($data['jam'] as $i => $jam) {
            $kegiatan = trim((string) ($data['kegiatan'][$i] ?? ''));
            if (! $jam && $kegiatan === '') {
                continue;
            }
            abort_unless($jam && $kegiatan !== '', 422, 'Setiap baris perlu jam dan kegiatan');
            $rows[] = ['jam' => $jam, 'kegiatan' => $kegiatan];
        }

        usort($rows, fn ($a, $b) => strcmp($a['jam'], $b['jam']));

        ScheduleSlot::where('class_id', $classId)->where('day', $day)->delete();
        foreach ($rows as $pos => $row) {
            ScheduleSlot::create([
                'class_id' => $classId,
                'day' => $day,
                'start_time' => $row['jam'],
                'activity' => $row['kegiatan'],
                'position' => $pos,
            ]);
        }

        return redirect()->route('guru.jadwal', ['day' => $day])
            ->with('toast', 'Jadwal tersimpan — orang tua melihat versi baru');
    }

    /**
     * Jadwal read-only untuk ortu.
     */
    public function showForParent(Request $request): View
    {
        $child = auth()->user()->child();
        abort_unless($child !== null, 403);

        [$day, $slots, $editing] = $this->resolve($request, $child->class_id);

        return view('ortu.jadwal', ['class' => $child->class, 'day' => $day, 'slots' => $slots]);
    }

    /**
     * @return array{0: int, 1: Collection<int, ScheduleSlot>, 2: bool}
     */
    private function resolve(Request $request, int $classId): array
    {
        $todayDow = today()->isWeekday() ? today()->dayOfWeekIso : 1;
        $day = (int) ($request->query('day', $todayDow));
        $day = ($day >= 1 && $day <= 5) ? $day : 1;

        $slots = ScheduleSlot::where('class_id', $classId)->where('day', $day)
            ->orderBy('start_time')->orderBy('position')->get();

        return [$day, $slots, $request->boolean('edit')];
    }

    /**
     * Jam masuk kelas hari ini (slot pertama; fallback 07.30) — paritas startTs().
     */
    public static function startTs(int $classId): Carbon
    {
        $dow = today()->isWeekday() ? today()->dayOfWeekIso : 1;
        $first = ScheduleSlot::where('class_id', $classId)->where('day', $dow)
            ->orderBy('start_time')->value('start_time');

        return today()->setTimeFromTimeString(substr((string) $first, 0, 5) ?: '07:30');
    }
}
