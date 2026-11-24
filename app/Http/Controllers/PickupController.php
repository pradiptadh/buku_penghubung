<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Pickup;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Support\Shell;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PickupController extends Controller
{
    /**
     * Tab Jemput guru — paritas guruJemput().
     */
    public function indexForTeacher(): View
    {
        $class = Teacher::with('class.students')->where('user_id', auth()->id())->firstOrFail()->class;
        $studentIds = $class->students->pluck('id');

        $statuses = Attendance::whereDate('date', today())
            ->whereIn('student_id', $studentIds)->get()->keyBy('student_id');

        $pickups = Pickup::whereDate('date', today())
            ->whereIn('student_id', $studentIds)->get()->keyBy('student_id');

        $present = $class->students->filter(fn ($s) => ($statuses[$s->id]->status ?? null) === 'H');

        $jamPulang = $class->scheduleFor(today()->isWeekday() ? today()->dayOfWeekIso : 1)->last();

        return view('guru.jemput', [
            'class' => $class,
            'present' => $present,
            'pickups' => $pickups,
            'remindMinutes' => (int) Setting::get('remind_minutes', '15'),
            'jamPulang' => $jamPulang?->jamLabel() ?? '–',
            'editId' => request()->query('edit'),
        ]);
    }

    /**
     * Simpan ambang pengingat (menit).
     */
    public function saveRemindSetting(Request $request): RedirectResponse
    {
        $data = $request->validate(['remind_minutes' => ['required', 'integer', 'in:15,30,60']]);
        Setting::set('remind_minutes', (string) $data['remind_minutes']);

        return back()->with('toast', 'Pengingat diatur');
    }

    /**
     * Satu anak siap dijemput.
     */
    public function ready(Student $student): RedirectResponse
    {
        $this->authorizeStudent($student);

        Pickup::updateOrCreate(
            ['student_id' => $student->id, 'date' => today()],
            ['status' => 'siap', 'ready_at' => now(), 'reminded_at' => null, 'otw' => false],
        );

        return back()->with('toast', "Orang tua {$student->name} diberi tahu");
    }

    /**
     * Kelas selesai — semua yang Hadir siap dijemput.
     */
    public function readyAll(): RedirectResponse
    {
        $class = Teacher::where('user_id', auth()->id())->firstOrFail()->class;
        $n = 0;

        foreach ($class->students as $student) {
            $hadir = Attendance::whereDate('date', today())->where('student_id', $student->id)->value('status') === 'H';
            $menunggu = Pickup::whereDate('date', today())->where('student_id', $student->id)
                ->whereIn('status', ['siap', 'dijemput'])->doesntExist();

            if ($hadir && $menunggu) {
                Pickup::create(['student_id' => $student->id, 'date' => today(), 'status' => 'siap', 'ready_at' => now()]);
                $n++;
            }
        }

        return back()->with('toast', "$n orang tua diberi tahu");
    }

    /**
     * Sudah dijemput (dengan orang tua sebagai pengjemput default).
     */
    public function picked(Student $student): RedirectResponse
    {
        $this->authorizeStudent($student);

        $pickup = Pickup::whereDate('date', today())->where('student_id', $student->id)->firstOrFail();
        $pickup->update([
            'status' => 'dijemput',
            'picked_at' => now(),
            'picked_by' => $pickup->picked_by ?: $student->parentUser?->name,
        ]);

        return back()->with('toast', "{$student->name} sudah dijemput");
    }

    /**
     * Edit box guru: status + jam + dijemput oleh (paritas pkSave).
     */
    public function update(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeStudent($student);

        $data = $request->validate([
            'status' => ['required', 'in:belajar,siap,dijemput'],
            'jam' => ['nullable', 'date_format:H:i'],
            'oleh' => ['nullable', 'string', 'max:60'],
        ]);

        $old = Pickup::whereDate('date', today())->where('student_id', $student->id)->first();
        $ts = $data['jam'] ? today()->setTimeFromTimeString($data['jam']) : null;

        if ($data['status'] === 'belajar') {
            $old?->delete();
        } elseif ($data['status'] === 'siap') {
            abort_unless($ts !== null, 422, 'Isi jam terlebih dahulu');
            Pickup::updateOrCreate(
                ['student_id' => $student->id, 'date' => today()],
                ['status' => 'siap', 'ready_at' => $ts, 'reminded_at' => null],
            );
        } else {
            abort_unless($ts !== null && $data['oleh'] !== null && $data['oleh'] !== '', 422, 'Isi jam dan siapa yang menjemput');
            Pickup::updateOrCreate(
                ['student_id' => $student->id, 'date' => today()],
                [
                    'status' => 'dijemput',
                    'ready_at' => $old && $old->ready_at ? min($old->ready_at, $ts) : $ts,
                    'picked_at' => $ts,
                    'picked_by' => $data['oleh'],
                ],
            );
        }

        return back()->with('toast', "Penjemputan {$student->name} diperbarui");
    }

    /**
     * Ortu: "Saya sedang di jalan".
     */
    public function otw(): RedirectResponse
    {
        $child = auth()->user()->child();
        abort_unless($child !== null, 403);

        Pickup::whereDate('date', today())->where('student_id', $child->id)
            ->update(['otw' => true]);

        return back()->with('toast', 'Guru diberi tahu Anda sedang di jalan');
    }

    private function authorizeStudent(Student $student): void
    {
        abort_unless(Shell::guruStudentIds()->contains($student->id), 403);
    }
}
