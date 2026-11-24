@extends('layouts.app')

@section('title', 'Kehadiran · '.auth()->user()->name)

@section('content')
  @php
      $statusLabel = ['H' => 'Hadir', 'S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa'];
  @endphp

  <div class="sec"><h3>Kehadiran hari ini</h3><span>{{ App\Support\Tgl::pendek(today()) }}</span></div>

  <div class="stats">
    <div class="stat"><b style="color:var(--green)">{{ $summary['H'] }}</b><span>Hadir</span></div>
    <div class="stat"><b style="color:var(--orange)">{{ $summary['S'] }}</b><span>Sakit</span></div>
    <div class="stat"><b style="color:var(--blue)">{{ $summary['I'] }}</b><span>Izin</span></div>
    <div class="stat"><b style="color:var(--red)">{{ $summary['A'] }}</b><span>Alpa</span></div>
  </div>

  @if ($summary['none'] > 0)
    <div class="note"><b>{{ $summary['none'] }} anak belum diisi.</b> Orang tua langsung melihat status begitu Anda mengetuk H / S / I / A.</div>
  @endif

  <div class="card">
    @foreach ($class->students as $s)
      @php
          $v = $attendances[$s->id]->status ?? null;
          $report = $reports[$s->id] ?? null;
      @endphp
      <div class="stu">
        <div class="avatar">{{ $s->initials }}</div>
        <div class="nm">
          <b>{{ $s->name }}</b>
          @if ($alerts[$s->id] ?? null)
            <x-safety-line :alert="$alerts[$s->id]" />
          @elseif ($report)
            <span class="small" style="color:#a64a17">
              Orang tua melapor: {{ $statusLabel[$report->type] }} — {{ $report->note }}
            </span>
          @else
            <span class="small muted">{{ $s->parentUser?->name }}</span>
          @endif
        </div>
        <div class="seg">
          @foreach (['H', 'S', 'I', 'A'] as $k)
            <form method="POST" action="{{ route('guru.attendance.toggle', $s) }}" style="display:contents">
              @csrf
              <input type="hidden" name="status" value="{{ $k }}">
              <button class="{{ $k }} {{ $v === $k ? 'on' : '' }}" type="submit"
                      aria-label="{{ $statusLabel[$k] }}" title="{{ $statusLabel[$k] }}">{{ $k }}</button>
            </form>
          @endforeach
        </div>
      </div>
    @endforeach
  </div>

  <form method="POST" action="{{ route('guru.attendance.all') }}">
    @csrf
    <button class="btn ghost full" type="submit">Tandai semua yang kosong sebagai Hadir</button>
  </form>

  <div class="note" style="margin-top:12px">
    <b>Fitur keselamatan:</b> setelah jam masuk ({{ $start ? $start->jamLabel() : '07.30' }}),
    anak yang ditandai <b>A</b> tanpa keterangan dari orang tua memicu alarm di HP orang tuanya.
    Orang tua wajib merespons, dan jawabannya muncul di bawah nama anak.
  </div>
@endsection
