<?php

namespace Tests\Feature;

use App\Models\AbsenceReport;
use App\Models\Attendance;
use App\Models\SafetyAlert;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SafetyAlertTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Konfirmasi "anak aman karena Sakit" → alarm selesai,
     * kehadiran otomatis S, laporan otomatis dibuat (paritas safeOk).
     */
    public function test_ack_marks_child_safe_and_writes_absence(): void
    {
        $this->seed(DatabaseSeeder::class);

        $jati = Student::where('name', 'Jati')->firstOrFail();
        $papa = User::where('email', 'papa.jati@sekolah.test')->firstOrFail();

        $this->actingAs($papa)
            ->post(route('ortu.safety.ack'), [
                'reason' => 'S',
                'note' => 'Demam, istirahat di rumah',
            ])
            ->assertRedirect();

        $alert = SafetyAlert::whereDate('date', today())->where('student_id', $jati->id)->first();
        $this->assertNotNull($alert->ack_at);
        $this->assertSame('aman', $alert->ack_type);
        $this->assertSame('S', Attendance::whereDate('date', today())->where('student_id', $jati->id)->value('status'));
        $this->assertNotNull(AbsenceReport::whereDate('date', today())->where('student_id', $jati->id)->first());
    }

    /**
     * safety:check membuat alarm untuk murid A tanpa keterangan setelah jam masuk.
     */
    public function test_check_command_creates_alarm_for_unexplained_absence(): void
    {
        $this->seed(DatabaseSeeder::class);

        $maya = Student::where('name', 'Maya')->firstOrFail(); // Kindy 2
        Attendance::updateOrCreate(
            ['student_id' => $maya->id, 'date' => today()],
            ['status' => 'A'],
        );

        // Lewati jam masuk 07.30 agar alarm boleh terpicu.
        now()->setTime(10, 0);
        Artisan::call('safety:check');

        $this->assertNotNull(
            SafetyAlert::whereDate('date', today())->where('student_id', $maya->id)->first()
        );
    }

    /**
     * safety:check menghapus alarm yang belum direpons bila status berubah dari A.
     */
    public function test_check_command_auto_clears_resolved_alarm(): void
    {
        $this->seed(DatabaseSeeder::class);

        $jati = Student::where('name', 'Jati')->firstOrFail();
        Attendance::whereDate('date', today())->where('student_id', $jati->id)
            ->update(['status' => 'H']);

        Artisan::call('safety:check');

        $this->assertNull(
            SafetyAlert::whereDate('date', today())->whereNull('ack_at')
                ->where('student_id', $jati->id)->first()
        );
    }
}
