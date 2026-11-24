<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Pickup;
use App\Models\Post;
use App\Models\SafetyAlert;
use App\Services\MetricsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PimpinanController extends Controller
{
    /**
     * Ringkasan hari ini — paritas pimRingkas().
     */
    public function ringkasan(): View
    {
        $classes = ClassRoom::with(['teachers.user', 'students'])->orderBy('id')->get();

        $tot = ['H' => 0, 'total' => 0];
        $perClass = [];

        foreach ($classes as $class) {
            $summary = MetricsService::attendanceSummary($class->id, today()->toDateString());
            $tot['H'] += $summary['H'];
            $tot['total'] += $summary['total'];

            $studentIds = $class->students->pluck('id');
            $pickups = Pickup::whereDate('date', today())->whereIn('student_id', $studentIds)->get();

            $perClass[] = [
                'class' => $class,
                'summary' => $summary,
                'metrics' => MetricsService::forClass($class),
                'dijemput' => $pickups->where('status', 'dijemput')->count(),
                'menunggu' => $pickups->where('status', 'siap'),
                'terlambat' => $pickups->where('status', 'siap')->filter(fn ($p) => $p->reminded_at !== null)->count(),
            ];
        }

        $posts = Post::whereDate('created_at', today())->count();
        $belumDitandatangani = Post::pribadi()->whereNull('signed_at')->count();
        $menungguJemput = Pickup::whereDate('date', today())->where('status', 'siap')->count();

        $alarms = SafetyAlert::whereDate('date', today())->with('student.class')->orderByDesc('alarm_at')->get();

        return view('pimpinan.ringkasan', compact('tot', 'perClass', 'posts', 'belumDitandatangani', 'menungguJemput', 'alarms'));
    }

    /**
     * Semua aktivitas kelas — paritas pimAktivitas().
     */
    public function aktivitas(Request $request): View
    {
        $filter = $request->query('kelas', 'semua');
        $classes = ClassRoom::orderBy('id')->get();

        $posts = Post::with(['teacher', 'target', 'class', 'views'])
            ->when($filter !== 'semua', fn ($q) => $q->where('class_id', $filter))
            ->orderByDesc('created_at')->get();

        return view('pimpinan.aktivitas', compact('classes', 'posts', 'filter'));
    }
}
