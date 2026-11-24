<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $rina;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->rina = User::where('email', 'ms.rina@sekolah.test')->firstOrFail();
    }

    /**
     * Ketuk H → tersimpan; ketuk H lagi → batal (paritas toggle prototipe).
     */
    public function test_toggle_marks_then_clears(): void
    {
        $aruna = Student::where('name', 'Aruna')->firstOrFail();

        $this->actingAs($this->rina)
            ->post(route('guru.attendance.toggle', $aruna), ['status' => 'H'])
            ->assertRedirect();

        $this->assertSame('H', Attendance::whereDate('date', today())
            ->where('student_id', $aruna->id)->value('status'));

        $this->actingAs($this->rina)
            ->post(route('guru.attendance.toggle', $aruna), ['status' => 'H'])
            ->assertRedirect();

        $this->assertNull(Attendance::whereDate('date', today())
            ->where('student_id', $aruna->id)->first());
    }

    /**
     * Tandai semua: Playgroup kosong hari ini (demo) → semua terisi H,
     * kecuali Dafa yang sudah melapor S.
     */
    public function test_mark_all_respects_parent_report(): void
    {
        $this->actingAs($this->rina)
            ->post(route('guru.attendance.all'))
            ->assertRedirect();

        $pg = Student::whereHas('class', fn ($q) => $q->where('name', 'Playgroup Bintang'))->get();
        $today = Attendance::whereDate('date', today())->whereIn('student_id', $pg->pluck('id'))->get();

        $this->assertCount($pg->count(), $today);
        $dafa = $today->firstWhere('student_id', $pg->firstWhere('name', 'Dafa')->id);
        $this->assertSame('S', $dafa->status);
        $this->assertSame('H', $today->firstWhere('student_id', $pg->firstWhere('name', 'Aruna')->id)->status);
    }

    /**
     * Guru hanya boleh murid kelasnya sendiri.
     */
    public function test_guru_cannot_toggle_other_class_student(): void
    {
        $maya = Student::where('name', 'Maya')->firstOrFail(); // Kindy 2

        $this->actingAs($this->rina)
            ->post(route('guru.attendance.toggle', $maya), ['status' => 'H'])
            ->assertForbidden();
    }
}
