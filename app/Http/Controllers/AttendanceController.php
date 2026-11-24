<?php

namespace App\Http\Controllers;

use App\Models\AbsenceReport;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\SafetyAlert;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\MetricsService;
use App\Support\Shell;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Tab Kehadiran guru — paritas guruHadir().
     */
    public function index(): View
    {
        $class = $this->myClass();
        $studentIds = $class->students->pluck('id');

        $attendances = Attendance::whereDate('date', today())
            ->whereIn('student_id', $studentIds)->get()->keyBy('student_id');

        $reports = AbsenceReport::whereDate('date', today())
            ->whereIn('student_id', $studentIds)->get()->keyBy('student_id');

        $alerts = SafetyAlert::whereDate('date', today())
            ->whereIn('student_id', $studentIds)->with(['student.parentUser', 'ackBy'])->get()->keyBy('student_id');

        $summary = MetricsService::attendanceSummary($class->id, today()->toDateString());
        $start = $class->scheduleFor(today()->isWeekday() ? today()->dayOfWeekIso : 1)->first();

        return view('guru.kehadiran', compact('class', 'attendances', 'reports', 'alerts', 'summary', 'start'));
    }

    /**
     * Ketuk H/S/I/A — nilai sama membatalkan (paritas toggle prototipe).
     */
    public function toggle(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:H,S,I,A']]);
        $this->authorizeStudent($student);

        $row = Attendance::whereDate('date', today())->where('student_id', $student->id)->first();

        if ($row && $row->status === $data['status']) {
            $row->delete();
        } else {
            Attendance::updateOrCreate(
                ['student_id' => $student->id, 'date' => today()],
                ['status' => $data['status']],
            );
        }

        return back();
    }

    /**
     * Tandai semua yang kosong sebagai Hadir (hormati laporan ortu).
     */
    public function markAll(): RedirectResponse
    {
        $class = $this->myClass();
        $reports = AbsenceReport::whereDate('date', today())->get()->keyBy('student_id');
        $n = 0;

        foreach ($class->students as $student) {
            $exists = Attendance::whereDate('date', today())->where('student_id', $student->id)->exists();
            if (! $exists) {
                Attendance::create([
                    'student_id' => $student->id,
                    'date' => today(),
                    'status' => $reports[$student->id]->type ?? 'H',
                ]);
                $n++;
            }
        }

        return back()->with('toast', $n > 0 ? "$n anak ditandai" : 'Semua sudah terisi');
    }

    private function myClass(): ClassRoom
    {
        $teacher = Teacher::with(['class.students.parentUser'])
            ->where('user_id', auth()->id())->firstOrFail();

        return $teacher->class;
    }

    private function authorizeStudent(Student $student): void
    {
        abort_unless(Shell::guruStudentIds()->contains($student->id), 403);
    }
}
