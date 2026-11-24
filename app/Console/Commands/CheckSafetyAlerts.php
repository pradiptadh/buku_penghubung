<?php

namespace App\Console\Commands;

use App\Models\AbsenceReport;
use App\Models\Attendance;
use App\Models\SafetyAlert;
use App\Notifications\SafetyAlarmNotification;
use Illuminate\Console\Command;

/**
 * Alarm keselamatan — paritas tick() prototipe:
 * anak berstatus A tanpa keterangan setelah jam masuk → alarm ke ortu + guru.
 */
class CheckSafetyAlerts extends Command
{
    protected $signature = 'safety:check';

    protected $description = 'Memicu alarm untuk murid berstatus A tanpa keterangan setelah jam masuk';

    public function handle(): int
    {
        $today = today()->toDateString();
        $now = now()->format('H:i');

        $absent = Attendance::with(['student.class.schedule', 'student.parentUser'])
            ->whereDate('date', $today)->where('status', 'A')->get();

        foreach ($absent as $attendance) {
            $student = $attendance->student;
            $hasAlert = SafetyAlert::whereDate('date', $today)->where('student_id', $student->id)->exists();
            $hasReport = AbsenceReport::whereDate('date', $today)->where('student_id', $student->id)->exists();

            if ($hasAlert || $hasReport) {
                continue;
            }

            // Jam masuk = slot pertama jadwal hari ini (weekday), fallback 07.30
            $dow = today()->isWeekday() ? today()->dayOfWeekIso : 1;
            $start = $student->class->schedule->where('day', $dow)
                ->sortBy('start_time')->first()?->start_time ?? '07:30:00';

            if ($now < substr($start, 0, 5)) {
                continue;
            }

            $alert = SafetyAlert::create([
                'student_id' => $student->id,
                'date' => $today,
                'alarm_at' => now(),
            ]);

            $student->parentUser?->notify(new SafetyAlarmNotification($alert));
            $student->class->teachers->each(
                fn ($t) => $t->user->notify(new SafetyAlarmNotification($alert))
            );
        }

        // Auto-clear: status berubah dari A dan belum direpons
        SafetyAlert::whereDate('date', $today)->whereNull('ack_at')
            ->whereHas('student.attendances', fn ($q) => $q
                ->whereDate('date', $today)->where('status', '!=', 'A'))
            ->get()
            ->each(fn ($alert) => $alert->delete());

        return self::SUCCESS;
    }
}
