@extends('layouts.app')

@section('title', 'Penilaian · '.auth()->user()->name)

@section('content')
  <div class="note">
    Angka di bawah dihitung otomatis dari aktivitas guru di aplikasi.
    Bintang dan catatan diisi oleh pimpinan.
  </div>

  @if ($errors->any())
    <div class="errors">
      @foreach ($errors->all() as $error)
        {{ $error }}<br>
      @endforeach
    </div>
  @endif

  @foreach ($teachers as $t)
    @php
        $scores = $t->rating?->scores ?? [0, 0, 0, 0];
        $m = $t->metrics;
    @endphp
    <div class="card">
      <div class="trow">
        <div class="avatar">{{ $t->user->initials ?? '?' }}</div>
        <div style="flex:1">
          <b>{{ $t->user->name }}</b>
          <div class="small muted">{{ $t->class?->name }}</div>
        </div>
        <div style="text-align:right">
          <div class="score">{{ App\Services\MetricsService::avg($scores) }}</div>
          <div class="small muted">dari 5</div>
        </div>
      </div>

      <div class="stats" style="margin:12px 0 6px">
        <div class="stat"><b>{{ $m['fill'] }}</b><span>Hari kehadiran terisi</span></div>
        <div class="stat"><b>{{ $m['posts'] }}</b><span>Info 7 hari</span></div>
        <div class="stat"><b>{{ $m['signed'] }}/{{ $m['ind'] }}</b><span>Catatan ditandatangani</span></div>
        <div class="stat"><b>{{ $m['read'] }}%</b><span>Info dibaca</span></div>
      </div>

      <form method="POST" action="{{ route('pimpinan.penilaian.store', $t->user) }}">
        @csrf
        @foreach ($kriteria as $i => $kriteriaLabel)
          <div class="crit">
            <span>{{ $kriteriaLabel }}</span>
            <div class="stars" role="group" aria-label="{{ $kriteriaLabel }}">
              @for ($n = 1; $n <= 5; $n++)
                <input type="radio" name="scores[{{ $i }}]" value="{{ $n }}"
                       id="st-{{ $t->user->id }}-{{ $i }}-{{ $n }}"
                       @checked(($scores[$i] ?? 0) === $n)>
                <label for="st-{{ $t->user->id }}-{{ $i }}-{{ $n }}"
                       class="{{ ($scores[$i] ?? 0) >= $n ? 'on' : '' }}">★</label>
              @endfor
            </div>
          </div>
        @endforeach

        <label class="f" for="note-{{ $t->user->id }}">Catatan pimpinan</label>
        <textarea id="note-{{ $t->user->id }}" name="note" style="min-height:64px"
                  placeholder="Apresiasi atau hal yang perlu ditingkatkan…">{{ $t->rating?->note }}</textarea>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;gap:8px">
          <span class="small muted">
            @if ($t->rating)
              Terakhir dinilai {{ $t->rating->leader?->name }}, {{ App\Support\Tgl::kapan($t->rating->updated_at) }}
            @else
              Belum pernah dinilai
            @endif
          </span>
          <button class="btn sm" type="submit">Simpan penilaian</button>
        </div>
      </form>
    </div>
  @endforeach
@endsection
