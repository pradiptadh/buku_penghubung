<?php

namespace App\Support;

use App\Models\Pickup;
use App\Models\Post;
use App\Models\SafetyAlert;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Data shell layout: judul, subjudul, tab navigasi, dan badge —
 * paritas render() di prototipe.
 */
class Shell
{
    /**
     * @return array{title: string, sub: string, tabs: list<array<string, string>>, badges: array<string, int>}|null
     */
    public static function build(Request $request): ?array
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return null;
        }

        $badges = [];
        $route = (string) $request->route()?->getName();
        $on = fn (string $prefix) => str_starts_with($route, $prefix);
        $tab = fn (string $key, string $label, string $icon, string $name) => [
            'key' => $key, 'label' => $label, 'icon' => $icon,
            'url' => route($name), 'current' => $on($name),
        ];

        return match ($user->role) {
            'guru' => self::forGuru($user, $tab, $badges),
            'ortu' => self::forOrtu($user, $tab, $badges),
            'pimpinan' => self::forPimpinan($user, $tab),
            default => ['title' => 'Admin sekolah', 'sub' => 'Kelola akun & data aplikasi', 'tabs' => [], 'badges' => []],
        };
    }

    /**
     * @param  callable(string,string,string,string): array{key: string, label: string, icon: string, url: string, current: bool}  $tab
     * @param  array<string, int>  $badges
     * @return array{title: string, sub: string, tabs: list<array<string, string>>, badges: array<string, int>}
     */
    private static function forGuru(User $user, callable $tab, array $badges): array
    {
        $teacher = Teacher::with(['class.students', 'user'])->where('user_id', $user->id)->first();
        $class = $teacher?->class;

        if ($class) {
            $studentIds = $class->students->pluck('id');

            $info = Post::where('class_id', $class->id)->pribadi()->whereNull('signed_at')->count();
            if ($info > 0) {
                $badges['info'] = $info;
            }

            $jemput = Pickup::whereDate('date', today())->whereIn('student_id', $studentIds)->where('status', 'siap')->count();
            if ($jemput > 0) {
                $badges['jemput'] = $jemput;
            }

            $hadir = SafetyAlert::whereDate('date', today())->whereIn('student_id', $studentIds)
                ->where(fn ($q) => $q->whereNull('ack_at')->orWhere('ack_type', 'darurat'))->count();
            if ($hadir > 0) {
                $badges['hadir'] = $hadir;
            }
        }

        return [
            'title' => (string) $user->name,
            'sub' => 'Guru · '.($class->name ?? '-'),
            'tabs' => [
                $tab('hadir', 'Kehadiran', 'check', 'guru.kehadiran'),
                $tab('info', 'Info', 'info', 'guru.info'),
                $tab('jemput', 'Jemput', 'car', 'guru.jemput'),
                $tab('jadwal', 'Jadwal', 'cal', 'guru.jadwal'),
            ],
            'badges' => $badges,
        ];
    }

    /**
     * @param  callable(string,string,string,string): array{key: string, label: string, icon: string, url: string, current: bool}  $tab
     * @param  array<string, int>  $badges
     * @return array{title: string, sub: string, tabs: list<array<string, string>>, badges: array<string, int>}
     */
    private static function forOrtu(User $user, callable $tab, array $badges): array
    {
        $child = $user->child();

        if ($child) {
            $unsigned = Post::where('class_id', $child->class_id)->pribadi()
                ->where('target_student_id', $child->id)->whereNull('signed_at')->count();

            $alarm = SafetyAlert::whereDate('date', today())
                ->where('student_id', $child->id)->whereNull('ack_at')->exists();

            $total = $unsigned + ($alarm ? 1 : 0);
            if ($total > 0) {
                $badges['beranda'] = $total;
            }
        }

        return [
            'title' => (string) $user->name,
            'sub' => 'Orang tua '.($child->name ?? '-').' · '.($child->class?->name ?? '-'),
            'tabs' => [
                $tab('beranda', 'Beranda', 'home', 'ortu.beranda'),
                $tab('info', 'Info', 'info', 'ortu.info'),
                $tab('hadir', 'Kehadiran', 'check', 'ortu.hadir'),
                $tab('jadwal', 'Jadwal', 'cal', 'ortu.jadwal'),
            ],
            'badges' => $badges,
        ];
    }

    /**
     * @param  callable(string,string,string,string): array{key: string, label: string, icon: string, url: string, current: bool}  $tab
     * @return array{title: string, sub: string, tabs: list<array<string, string>>, badges: array<string, int>}
     */
    private static function forPimpinan(User $user, callable $tab): array
    {
        return [
            'title' => $user->position ?: (string) $user->name,
            'sub' => 'Pimpinan · semua kelas',
            'tabs' => [
                $tab('ringkas', 'Ringkasan', 'chart', 'pimpinan.ringkasan'),
                $tab('aktivitas', 'Aktivitas', 'info', 'pimpinan.aktivitas'),
                $tab('nilai', 'Penilaian', 'star', 'pimpinan.penilaian'),
                $tab('data', 'Data murid', 'users', 'pimpinan.data'),
            ],
            'badges' => [],
        ];
    }

    /**
     * Murid milik guru yang sedang login (untuk pengecekan kepemilikan).
     */
    public static function guruStudentIds(): Collection
    {
        $classId = Teacher::where('user_id', Auth::id())->value('class_id');

        return Student::where('class_id', $classId)->pluck('id');
    }
}
