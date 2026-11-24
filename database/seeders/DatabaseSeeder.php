<?php

namespace Database\Seeders;

use App\Models\AbsenceReport;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Pickup;
use App\Models\Post;
use App\Models\PostView;
use App\Models\Rating;
use App\Models\SafetyAlert;
use App\Models\ScheduleSlot;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Data contoh — paritas penuh seed() di prototipe v3.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set('remind_minutes', '15');

        /* ---------- admin & pimpinan ---------- */
        User::create([
            'name' => 'Admin Sekolah',
            'email' => 'admin@sekolah.test',
            'password' => Hash::make(User::defaultPasswordFor('admin')),
            'role' => 'admin',
        ]);

        $leaders = ['Kepala Sekolah', 'Wakil Kepala Sekolah – Kurikulum', 'Wakil Kepala Sekolah – Kesiswaan'];
        foreach ($leaders as $i => $leader) {
            User::create([
                'name' => $leader,
                'email' => 'pimpinan'.($i + 1).'@sekolah.test',
                'password' => Hash::make(User::defaultPasswordFor('pimpinan')),
                'role' => 'pimpinan',
                'position' => $leader,
            ]);
        }

        /* ---------- kelas ---------- */
        $classes = [
            'pg' => ['name' => 'Playgroup Bintang', 'unit' => 'Playgroup'],
            'k1' => ['name' => 'Kindy 1 Pelangi', 'unit' => 'Kindy'],
            'k2' => ['name' => 'Kindy 2 Matahari', 'unit' => 'Kindy'],
        ];
        $classIds = [];
        foreach ($classes as $slug => $class) {
            $classIds[$slug] = ClassRoom::create($class)->id;
        }

        /* ---------- guru ---------- */
        foreach (['Ms. Rina' => 'pg', 'Ms. Dewi' => 'k1', 'Ms. Sari' => 'k2'] as $teacherName => $slug) {
            $user = User::create([
                'name' => $teacherName,
                'email' => Str::slug($teacherName, '.').'@sekolah.test',
                'password' => Hash::make(User::defaultPasswordFor('guru')),
                'role' => 'guru',
            ]);
            Teacher::create(['user_id' => $user->id, 'class_id' => $classIds[$slug]]);
        }

        /* ---------- murid + akun ortu ---------- */
        $names = [
            'pg' => ['Aruna', 'Bima', 'Citra', 'Dafa', 'Elok', 'Fajar'],
            'k1' => ['Gita', 'Hanif', 'Intan', 'Jati', 'Kirana', 'Lintang'],
            'k2' => ['Maya', 'Nanda', 'Oki', 'Putri', 'Raka', 'Sinta'],
        ];
        $students = [];
        foreach ($names as $slug => $list) {
            foreach ($list as $i => $name) {
                $parent = ($i % 2 ? 'Papa ' : 'Mama ').$name;
                $parentUser = User::create([
                    'name' => $parent,
                    'email' => Str::slug($parent, '.').'@sekolah.test',
                    'password' => Hash::make(User::defaultPasswordFor('ortu')),
                    'role' => 'ortu',
                ]);
                $students[$slug.$i] = Student::create([
                    'name' => $name,
                    'class_id' => $classIds[$slug],
                    'parent_user_id' => $parentUser->id,
                ]);
            }
        }

        /* ---------- jadwal (JADWAL di prototipe) ---------- */
        $jadwal = $this->jadwal();
        foreach ($classes as $slug => $class) {
            foreach ($jadwal[$class['unit']] as $day => $rows) {
                foreach ($rows as $pos => [$time, $activity]) {
                    ScheduleSlot::create([
                        'class_id' => $classIds[$slug],
                        'day' => $day,
                        'start_time' => str_replace('.', ':', $time),
                        'activity' => $activity,
                        'position' => $pos,
                    ]);
                }
            }
        }

        /* ---------- kehadiran 6 hari sekolah terakhir (pola prototipe) ---------- */
        $si = 0;
        foreach ($this->lastSchoolDays(6) as $di => $day) {
            $si = 0;
            foreach ($students as $student) {
                if ($day === today()->toDateString() && $student->class_id === $classIds['pg']) {
                    $si++;

                    continue; // Playgroup belum diisi hari ini (untuk demo)
                }
                $r = ($si * 7 + $di * 13) % 23;
                Attendance::create([
                    'student_id' => $student->id,
                    'date' => $day,
                    'status' => $r === 0 ? 'S' : ($r === 5 ? 'I' : ($r === 11 && $di < 2 ? 'A' : 'H')),
                ]);
                $si++;
            }
        }

        /* ---------- laporan ortu + contoh alarm (Jati, Kindy 1) ---------- */
        AbsenceReport::create([
            'student_id' => $students['pg3']->id,
            'date' => today(),
            'type' => 'S',
            'note' => 'Demam sejak semalam',
            'reported_at' => now()->subHours(4),
        ]);

        Attendance::updateOrCreate(
            ['student_id' => $students['k13']->id, 'date' => today()],
            ['status' => 'A'],
        );
        SafetyAlert::create([
            'student_id' => $students['k13']->id,
            'date' => today(),
            'alarm_at' => now()->subMinutes(30),
        ]);

        /* ---------- penjemputan Kindy 2 ---------- */
        $k2 = Student::where('class_id', $classIds['k2'])->orderBy('id')->get();
        foreach ($k2 as $i => $student) {
            if ($i < 4) {
                Pickup::create([
                    'student_id' => $student->id,
                    'date' => today(),
                    'status' => 'dijemput',
                    'ready_at' => now()->subMinutes(50),
                    'picked_at' => now()->subMinutes(40),
                    'picked_by' => $student->parentUser->name,
                ]);
            } else {
                Pickup::create([
                    'student_id' => $student->id,
                    'date' => today(),
                    'status' => 'siap',
                    'ready_at' => now()->subMinutes(22),
                    'reminded_at' => now()->subMinutes(7),
                ]);
            }
        }

        /* ---------- posts ---------- */
        $teacherId = fn (string $slug): int => (int) Teacher::where('class_id', $classIds[$slug])->value('user_id');

        foreach ($classes as $slug => $class) {
            $isPg = $class['unit'] === 'Playgroup';

            Post::create([
                'class_id' => $classIds[$slug], 'teacher_id' => $teacherId($slug),
                'periode' => 'tahunan', 'kategori' => 'Pengumuman',
                'judul' => 'Kalender kegiatan tahun ajaran',
                'isi' => "• Desember: Pentas seni & pembagian rapor semester 1\n• Februari: Kunjungan ke kebun binatang\n• April: Hari Kartini – parade busana daerah\n• Juni: Pelepasan & pentas akhir tahun",
                'created_at' => now()->subDays(9),
            ]);
            Post::create([
                'class_id' => $classIds[$slug], 'teacher_id' => $teacherId($slug),
                'periode' => 'bulanan', 'kategori' => 'Pembelajaran',
                'judul' => 'Tema bulan ini: Tanaman di Sekitarku',
                'isi' => $isPg
                    ? 'Anak-anak akan mengenal bagian tanaman lewat kegiatan sensori: meraba daun, mencium bunga, dan menyiram tanaman.'
                    : 'Anak-anak belajar bagian tanaman, proses tumbuh, dan menanam kacang hijau. Kosakata baru: akar, batang, daun, biji.',
                'created_at' => now()->subDays(6),
            ]);
            Post::create([
                'class_id' => $classIds[$slug], 'teacher_id' => $teacherId($slug),
                'periode' => 'mingguan', 'kategori' => 'Perlengkapan',
                'judul' => 'Persiapan minggu ini',
                'isi' => 'Mohon membawa: 1 gelas plastik bekas, kapas, dan baju ganti. Jumat memakai seragam olahraga.',
                'created_at' => now()->subDays(3),
            ]);
            Post::create([
                'class_id' => $classIds[$slug], 'teacher_id' => $teacherId($slug),
                'periode' => 'harian', 'kategori' => 'Kegiatan',
                'judul' => 'Kegiatan hari ini',
                'isi' => $isPg
                    ? 'Hari ini anak-anak bermain pasir dan air, lalu mendengarkan dongeng "Biji Kecil yang Berani". Semua makan snack dengan lahap.'
                    : 'Hari ini kami menanam kacang hijau di gelas kapas. Anak-anak menggambar perkiraan tanamannya besok.',
                'created_at' => now()->subHours($slug === 'pg' ? 26 : 2),
            ]);
        }

        Post::create([
            'class_id' => $classIds['pg'], 'teacher_id' => $teacherId('pg'),
            'periode' => 'harian', 'kategori' => 'Perkembangan anak',
            'judul' => 'Catatan untuk Aruna',
            'isi' => 'Hari ini Aruna sempat menangis saat ditinggal, namun tenang setelah 5 menit dan mau bergabung di circle time. Mohon dibiasakan salam perpisahan singkat di gerbang ya.',
            'target_student_id' => $students['pg0']->id,
            'created_at' => now()->subHours(3),
        ]);
        Post::create([
            'class_id' => $classIds['k1'], 'teacher_id' => $teacherId('k1'),
            'periode' => 'harian', 'kategori' => 'Kesehatan',
            'judul' => 'Catatan untuk Gita',
            'isi' => 'Gita terlihat agak pilek dan kurang bersemangat saat bermain di luar. Mohon dipantau di rumah.',
            'target_student_id' => $students['k10']->id,
            'created_at' => now()->subHours(5),
        ]);
        Post::create([
            'class_id' => $classIds['k2'], 'teacher_id' => $teacherId('k2'),
            'periode' => 'harian', 'kategori' => 'Perkembangan anak',
            'judul' => 'Catatan untuk Maya',
            'isi' => 'Maya hari ini berani maju bercerita di depan kelas untuk pertama kalinya. Hebat!',
            'target_student_id' => $students['k20']->id,
            'created_at' => now()->subHours(27),
            'signed_by' => $students['k20']->parent_user_id,
            'signed_at' => now()->subHours(25),
        ]);

        /* ---------- sebagian ortu sudah membaca info massal ((pi+si)%3!==0) ---------- */
        $pi = 0;
        Post::massal()->with('class.students')->get()->each(function (Post $post) use (&$pi) {
            foreach ($post->class->students->values() as $si => $student) {
                if (($pi + $si) % 3 !== 0) {
                    PostView::create([
                        'post_id' => $post->id,
                        'student_id' => $student->id,
                        'seen_at' => $post->created_at->copy()->addHour(),
                    ]);
                }
            }
            $pi++;
        });

        /* ---------- penilaian ---------- */
        $dewi = User::where('name', 'Ms. Dewi')->firstOrFail();
        Rating::create([
            'teacher_id' => $dewi->id,
            'leader_id' => User::where('role', 'pimpinan')->firstOrFail()->id,
            'scores' => [5, 4, 4, 5],
            'note' => 'Info harian konsisten dan jelas.',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);
    }

    /**
     * @return array<string, array<int, list<list<string>>>>
     */
    private function jadwal(): array
    {
        return [
            'Playgroup' => [
                1 => [['07.30', 'Penyambutan & bermain bebas'], ['08.00', 'Circle time: lagu & doa'], ['08.30', 'Sensori: bermain pasir & air'], ['09.15', 'Snack time'], ['09.45', 'Motorik kasar di halaman'], ['10.30', 'Pulang']],
                2 => [['07.30', 'Penyambutan & bermain bebas'], ['08.00', 'Circle time'], ['08.30', 'Seni: melukis dengan jari'], ['09.15', 'Snack time'], ['09.45', 'Dongeng & boneka tangan'], ['10.30', 'Pulang']],
                3 => [['07.30', 'Penyambutan'], ['08.00', 'Circle time'], ['08.30', 'Musik & gerak'], ['09.15', 'Snack time'], ['09.45', 'Bermain balok'], ['10.30', 'Pulang']],
                4 => [['07.30', 'Penyambutan'], ['08.00', 'Circle time'], ['08.30', 'Praktik hidup: menuang & menyendok'], ['09.15', 'Snack time'], ['09.45', 'Berkebun mini'], ['10.30', 'Pulang']],
                5 => [['07.30', 'Senam pagi bersama'], ['08.00', 'Circle time'], ['08.30', 'Bermain peran'], ['09.15', 'Makan bersama (bekal sehat)'], ['09.45', 'Show and tell'], ['10.30', 'Pulang']],
            ],
            'Kindy' => [
                1 => [['07.30', 'Upacara kecil & doa'], ['08.00', 'Literasi: mengenal huruf'], ['09.00', 'Snack time'], ['09.30', 'Numerasi: berhitung benda'], ['10.30', 'Bermain di luar'], ['11.30', 'Pulang']],
                2 => [['07.30', 'Circle time'], ['08.00', 'Sains sederhana'], ['09.00', 'Snack time'], ['09.30', 'Seni & prakarya'], ['10.30', 'Olahraga'], ['11.30', 'Pulang']],
                3 => [['07.30', 'Circle time'], ['08.00', 'Literasi: bercerita'], ['09.00', 'Snack time'], ['09.30', 'Bahasa Inggris'], ['10.30', 'Musik'], ['11.30', 'Pulang']],
                4 => [['07.30', 'Circle time'], ['08.00', 'Numerasi: pola & bentuk'], ['09.00', 'Snack time'], ['09.30', 'Agama & budi pekerti'], ['10.30', 'Perpustakaan'], ['11.30', 'Pulang']],
                5 => [['07.30', 'Senam pagi bersama'], ['08.00', 'Proyek tema'], ['09.00', 'Makan bersama (bekal sehat)'], ['09.30', 'Show and tell'], ['10.30', 'Bermain bebas'], ['11.30', 'Pulang']],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function lastSchoolDays(int $n): array
    {
        $days = [];
        $d = today();
        while (count($days) < $n) {
            if ($d->isWeekday()) {
                array_unshift($days, $d->toDateString());
            }
            $d = $d->copy()->subDay();
        }

        return $days;
    }
}
