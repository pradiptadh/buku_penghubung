<?php

namespace App\Http\Controllers;

use App\Models\AbsenceReport;
use App\Models\Attendance;
use App\Models\Pickup;
use App\Models\Post;
use App\Models\SafetyAlert;
use App\Services\MetricsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrtuController extends Controller
{
    /**
     * Beranda ortu — paritas ortuBeranda().
     */
    public function beranda(): View
    {
        $child = auth()->user()->child();
        abort_unless($child !== null, 403);

        $child->load(['class.teachers.user', 'parentUser']);

        $alert = SafetyAlert::whereDate('date', today())->where('student_id', $child->id)->first();
        $pickup = Pickup::whereDate('date', today())->where('student_id', $child->id)->first()
            ?? new Pickup(['status' => 'belajar']);

        $hadir = Attendance::whereDate('date', today())->where('student_id', $child->id)->value('status');

        $wajib = Post::with('teacher')->where('class_id', $child->class_id)
            ->where('target_student_id', $child->id)->whereNull('signed_at')
            ->orderByDesc('created_at')->get();

        $infoHariIni = Post::with('teacher')->where('class_id', $child->class_id)
            ->massal()->whereDate('created_at', today())
            ->orderByDesc('created_at')->get();

        // Paritas prototipe: beranda hanya menandai info hari ini sebagai dilihat
        PostController::markSeen($child, true);

        return view('ortu.beranda', [
            'child' => $child,
            'guru' => $child->class->teachers->first()?->user,
            'alert' => $alert,
            'pickup' => $pickup,
            'hadir' => $hadir,
            'wajib' => $wajib,
            'infoHariIni' => $infoHariIni,
        ]);
    }

    /**
     * Tab Info ortu — semua info kelas anak + tandai dilihat.
     */
    public function info(Request $request): View
    {
        $child = auth()->user()->child();
        abort_unless($child !== null, 403);

        $filter = $request->query('filter', 'semua');

        $posts = Post::with('teacher')->where('class_id', $child->class_id)
            ->where(fn ($q) => $q->massal()->orWhere('target_student_id', $child->id))
            ->when($filter === 'pribadi', fn ($q) => $q->pribadi())
            ->when(in_array($filter, PostController::PERIODE, true),
                fn ($q) => $q->where('periode', $filter)->massal())
            ->orderByDesc('created_at')->get();

        PostController::markSeen($child);

        return view('ortu.info', ['child' => $child, 'posts' => $posts, 'filter' => $filter]);
    }

    /**
     * Tab Kehadiran ortu — riwayat 6 hari + lapor tidak masuk.
     */
    public function hadir(): View
    {
        $child = auth()->user()->child();
        abort_unless($child !== null, 403);

        $days = MetricsService::lastSchoolDays(6);
        $byDate = Attendance::where('student_id', $child->id)
            ->whereIn('date', $days)->get()->keyBy(
                fn ($a) => $a->date->toDateString()
            );

        $report = AbsenceReport::whereDate('date', today())->where('student_id', $child->id)->first();

        return view('ortu.hadir', ['child' => $child, 'days' => $days, 'byDate' => $byDate, 'report' => $report]);
    }

    /**
     * Lapor tidak masuk — paritas absence().
     */
    public function absence(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:S,I'],
            'note' => ['required', 'string', 'max:100'],
        ]);

        $child = $request->user()->child();
        abort_unless($child !== null, 403);

        AbsenceReport::updateOrCreate(
            ['student_id' => $child->id, 'date' => today()],
            ['type' => $data['type'], 'note' => $data['note'], 'reported_at' => now()],
        );

        return back()->with('toast', 'Laporan terkirim ke guru');
    }
}
