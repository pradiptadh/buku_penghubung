<?php

namespace App\Http\Controllers;

use App\Models\AbsenceReport;
use App\Models\Attendance;
use App\Models\SafetyAlert;
use App\Models\User;
use App\Notifications\SafetyAlarmNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SafetyController extends Controller
{
    /**
     * Ortu konfirmasi anak aman — paritas safeOk().
     * Alasan S/I otomatis jadi laporan + kehadiran.
     */
    public function ack(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'in:S,I,T'],
            'note' => ['required', 'string', 'max:100'],
        ]);

        $child = $request->user()->child();
        abort_unless($child !== null, 403);

        $alert = SafetyAlert::whereDate('date', today())->where('student_id', $child->id)->firstOrFail();
        abort_if($alert->ack_at !== null, 403);

        $alert->update([
            'ack_type' => 'aman',
            'ack_reason' => $data['reason'],
            'ack_note' => $data['note'],
            'ack_by' => $request->user()->id,
            'ack_at' => now(),
        ]);

        if ($data['reason'] !== 'T') {
            AbsenceReport::updateOrCreate(
                ['student_id' => $child->id, 'date' => today()],
                ['type' => $data['reason'], 'note' => $data['note'], 'reported_at' => now()],
            );
            Attendance::updateOrCreate(
                ['student_id' => $child->id, 'date' => today()],
                ['status' => $data['reason']],
            );
        }

        return back()->with('toast', 'Alarm dimatikan · sekolah sudah dikabari');
    }

    /**
     * Ortu tidak tahu keberadaan anak — eskalasi darurat (paritas safeSos()).
     */
    public function sos(Request $request): RedirectResponse
    {
        $child = $request->user()->child();
        abort_unless($child !== null, 403);

        $alert = SafetyAlert::whereDate('date', today())->where('student_id', $child->id)->firstOrFail();
        abort_if($alert->ack_at !== null, 403);

        $alert->update([
            'ack_type' => 'darurat',
            'ack_at' => now(),
            'ack_by' => $request->user()->id,
        ]);

        $child->class->teachers->each(
            fn ($t) => $t->user->notify(new SafetyAlarmNotification($alert, true))
        );
        User::where('role', 'pimpinan')->get()->each(
            fn ($u) => $u->notify(new SafetyAlarmNotification($alert, true))
        );

        return back()->with('toast', 'Laporan darurat terkirim ke guru dan pimpinan');
    }

    /**
     * Senyapkan bunyi alarm (alarm tetap aktif) — paritas safeMute().
     */
    public function mute(Request $request): RedirectResponse
    {
        $child = $request->user()->child();
        abort_unless($child !== null, 403);

        SafetyAlert::whereDate('date', today())->where('student_id', $child->id)
            ->whereNull('ack_at')->update(['muted' => true]);

        return back();
    }
}
