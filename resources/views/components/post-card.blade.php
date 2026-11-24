@props(['post', 'ctx'])
@php
    $ind = $post->target_student_id !== null;
    $kind = $ind ? 'pribadi' : $post->periode;
    $total = App\Models\Student::where('class_id', $post->class_id)->count();
    $seen = $post->views->count();
    $persen = $total > 0 ? (int) round($seen / $total * 100) : 0;
@endphp
<article class="card post p-{{ $kind }}">
    <div class="meta">
        @if ($ind)
            <span class="tag t-pribadi">Pribadi · {{ $post->target?->name }}</span>
        @endif
        <span class="tag t-{{ $post->periode }}">{{ ucfirst($post->periode) }}</span>
        <span class="tag">{{ $post->kategori }}</span>
        @if ($ctx === 'pimpinan')
            <span class="tag">{{ $post->class?->name }}</span>
        @endif
    </div>

    <h4>{{ $post->judul }}</h4>
    <p>{{ $post->isi }}</p>
    <div class="by">{{ $post->teacher?->name }} · {{ App\Support\Tgl::kapan($post->created_at) }}</div>

    @if ($ctx === 'ortu' && $ind)
        @if ($post->signed_at)
            <div class="signed">✓ Ditandatangani {{ $post->signer?->name }} · {{ App\Support\Tgl::kapan($post->signed_at) }}</div>
        @else
            <div class="signbox">
                <form method="POST" action="{{ route('ortu.sign', $post) }}">
                    @csrf
                    <label>
                        <input type="checkbox" data-chk="{{ $post->id }}">
                        <span>Saya sudah membaca dan memahami catatan ini</span>
                    </label>
                    <button class="btn sm" type="submit" data-sign="{{ $post->id }}" disabled>
                        Tanda tangani sebagai {{ $post->target?->parentUser?->name }}
                    </button>
                </form>
            </div>
        @endif
    @endif

    @if ($ctx !== 'ortu')
        @if ($ind)
            <div style="margin-top:8px">
                @if ($post->signed_at)
                    <span class="tag ok">✓ Dibaca &amp; ditandatangani {{ $post->signer?->name }} · {{ App\Support\Tgl::kapan($post->signed_at) }}</span>
                @else
                    <span class="tag wait">Menunggu tanda baca orang tua</span>
                @endif
            </div>
        @else
            <div class="small muted" style="margin-top:8px">Dilihat {{ $seen }} dari {{ $total }} orang tua</div>
            <div class="bar"><i style="width:{{ $persen }}%"></i></div>
        @endif
    @endif
</article>
