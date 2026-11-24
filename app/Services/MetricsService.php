<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\PostView;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Metrik otomatis untuk Pimpinan — paritas metrics() di prototipe.
 */
class MetricsService
{
    /**
     * N hari sekolah terakhir (weekday saja; hari ini ikut bila weekday).
     *
     * @return list<string> tanggal Y-m-d, urut lama → baru
     */
    public static function lastSchoolDays(int $n): array
    {
        $days = [];
        $d = CarbonImmutable::today();

        while (count($days) < $n) {
            if ($d->isWeekday()) {
                array_unshift($days, $d->toDateString());
            }
            $d = $d->subDay();
        }

        if (today()->isWeekday() && ! in_array(today()->toDateString(), $days, true)) {
            $days[] = today()->toDateString();
            array_shift($days);
        }

        return $days;
    }

    /**
     * Ringkasan H/S/I/A satu kelas pada satu tanggal.
     *
     * @return array{H: int, S: int, I: int, A: int, none: int, total: int, filled: bool}
     */
    public static function attendanceSummary(int $classId, string $date): array
    {
        $studentIds = Student::where('class_id', $classId)->pluck('id');

        $out = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0, 'none' => 0, 'total' => $studentIds->count(), 'filled' => false];

        $byStudent = Attendance::whereDate('date', $date)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        foreach ($studentIds as $sid) {
            $status = $byStudent->get($sid)?->status;
            if ($status === null) {
                $out['none']++;
            } else {
                $out[$status]++;
            }
        }
        $out['filled'] = $out['none'] === 0 && $out['total'] > 0;

        return $out;
    }

    /**
     * Metrik per kelas: fill, posts 7 hari, catatan pribadi & tanda tangan, read %.
     *
     * @return array{fill: string, posts: int, ind: int, signed: int, read: int}
     */
    public static function forClass(ClassRoom $class): array
    {
        $studentIds = $class->students->pluck('id');
        $kids = $studentIds->count();

        $fill = 0;
        foreach (self::lastSchoolDays(5) as $day) {
            $filledCount = Attendance::whereDate('date', $day)->whereIn('student_id', $studentIds)->count();
            if ($kids > 0 && $filledCount === $kids) {
                $fill++;
            }
        }

        $mass = Post::where('class_id', $class->id)->massal()->get(['id']);
        $seen = PostView::whereIn('post_id', $mass->pluck('id'))->count();
        $total = $mass->count() * $kids;

        return [
            'fill' => $fill.'/5',
            'posts' => Post::where('class_id', $class->id)->where('created_at', '>=', now()->subDays(7))->count(),
            'ind' => Post::where('class_id', $class->id)->pribadi()->count(),
            'signed' => Post::where('class_id', $class->id)->pribadi()->whereNotNull('signed_at')->count(),
            'read' => $total > 0 ? (int) round($seen / $total * 100) : 0,
        ];
    }

    /**
     * Rata-rata bintang dari array skor, "–" bila kosong.
     */
    public static function avg(Collection|array $scores): string
    {
        $scores = collect($scores)->filter();
        if ($scores->isEmpty()) {
            return '–';
        }

        return number_format($scores->sum() / $scores->count(), 1, ',', '');
    }
}
