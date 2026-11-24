<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\PostView;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    public const PERIODE = ['harian', 'mingguan', 'bulanan', 'tahunan'];

    public const KATEGORI = ['Kegiatan', 'Pembelajaran', 'Pengumuman', 'Perlengkapan', 'Kesehatan', 'Perkembangan anak'];

    /**
     * Tab Info guru — paritas guruInfo().
     */
    public function indexForTeacher(Request $request): View
    {
        $filter = $request->query('filter', 'semua');
        $class = $this->myClass();

        $posts = Post::with(['teacher', 'target', 'views'])
            ->where('class_id', $class->id)
            ->when($filter === 'pribadi', fn ($q) => $q->pribadi())
            ->when(in_array($filter, self::PERIODE, true), fn ($q) => $q->where('periode', $filter)->massal())
            ->orderByDesc('created_at')
            ->get();

        return view('guru.info', compact('class', 'posts', 'filter'));
    }

    /**
     * Form buat info baru.
     */
    public function create(): View
    {
        $class = $this->myClass();

        return view('guru.info-create', compact('class'));
    }

    /**
     * Simpan info — massal atau pribadi (paritas compose/send).
     */
    public function store(Request $request): RedirectResponse
    {
        $teacher = Teacher::where('user_id', auth()->id())->firstOrFail();
        $studentIds = $teacher->class->students->pluck('id')->all();

        $data = $request->validate([
            'target' => ['required', Rule::in(array_merge(['all'], $studentIds))],
            'periode' => ['required', 'in:'.implode(',', self::PERIODE)],
            'kategori' => ['required', 'in:'.implode(',', self::KATEGORI)],
            'judul' => ['required', 'string', 'max:80'],
            'isi' => ['required', 'string'],
        ]);

        Post::create([
            'class_id' => $teacher->class_id,
            'teacher_id' => auth()->id(),
            'periode' => $data['periode'],
            'kategori' => $data['kategori'],
            'judul' => $data['judul'],
            'isi' => $data['isi'],
            'target_student_id' => $data['target'] === 'all' ? null : $data['target'],
        ]);

        $toast = $data['target'] === 'all'
            ? 'Terkirim ke semua orang tua '.$teacher->class->name
            : 'Terkirim — menunggu tanda baca orang tua';

        return redirect()->route('guru.info')->with('toast', $toast);
    }

    /**
     * Tanda baca orang tua pada catatan pribadi anaknya (paritas sign).
     */
    public function sign(Post $post): RedirectResponse
    {
        $child = auth()->user()->child();
        abort_unless($child && $post->target_student_id === $child->id, 403);
        abort_if($post->signed_at, 403);

        $post->update(['signed_by' => auth()->id(), 'signed_at' => now()]);

        return back()->with('toast', 'Terima kasih, tanda baca tercatat');
    }

    /**
     * Tandai info massal sudah dilihat (paritas seen di render()).
     *
     * @param  bool  $todayOnly  beranda hanya menandai info hari ini
     */
    public static function markSeen(Student $child, bool $todayOnly = false): void
    {
        Post::where('class_id', $child->class_id)
            ->massal()
            ->when($todayOnly, fn ($q) => $q->whereDate('created_at', today()))
            ->get(['id'])
            ->each(fn ($p) => PostView::firstOrCreate(
                ['post_id' => $p->id, 'student_id' => $child->id],
                ['seen_at' => now()],
            ));
    }

    private function myClass(): ClassRoom
    {
        return Teacher::with('class.students')
            ->where('user_id', auth()->id())->firstOrFail()->class;
    }
}
