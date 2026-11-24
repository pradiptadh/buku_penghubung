@extends('layouts.app')

@section('title', 'Ringkasan · '.auth()->user()->name)

@section('content')
  <div class="sec"><h3>Hari ini</h3><span>{{ App\Support\Tgl::panjang(today()) }}</span></div>

  <div class="stats">
    <div class="stat"><b>{{ $tot['H'] }}/{{ $tot['total'] }}</b><span>Hadir</span></div>
    <div class="stat"><b>{{ $posts }}</b><span>Info terkirim</span></div>
    <div class="stat"><b style="color:var(--red)">{{ $belumDitandatangani }}</b><span>Belum ditandatangani</span></div>
    <div class="stat"><b style="color:var(--orange)">{{ $menungguJemput }}</b><span>Menunggu jemput</span></div>
  </div>

  <div class="sec"><h3>Keselamatan anak</h3><span>{{ $alarms->count() }} alarm hari ini</span></div>
  <div class="card">
    @if ($alarms->isNotEmpty())
      @foreach ($alarms as $alarm)
        <div class="stu">
          <div class="avatar">{{ $alarm->student->initials }}</div>
          <div class="nm">
            <b>{{ $alarm->student->name }} · {{ $alarm->student->class->name }}</b>
            <x-safety-line :alert="$alarm" />
          </div>
        </div>
      @endforeach
    @else
      <span class="muted">Tidak ada anak yang absen tanpa keterangan.</span>
    @endif
  </div>

  <div class="sec"><h3>Per kelas</h3><span>{{ count($perClass) }} kelas</span></div>

  @foreach ($perClass as $row)
    @php
        $teacher = $row['class']->teachers->first()?->user;
        $m = $row['metrics'];
        $s = $row['summary'];
    @endphp
    <div class="card">
      <div class="trow">
        <div class="avatar">{{ $teacher?->initials ?? '?' }}</div>
        <div style="flex:1">
          <b>{{ $row['class']->name }}</b>
          <div class="small muted">{{ $teacher?->name }} · {{ $row['class']->unit }}</div>
        </div>
        @if ($s['filled'])
          <span class="tag ok">Kehadiran terisi</span>
        @else
          <span class="tag wait">Kehadiran belum lengkap</span>
        @endif
      </div>
      <div class="small" style="margin-top:10px;display:grid;grid-template-columns:1fr 1fr;gap:4px 12px">
        <span>Hadir <b>{{ $s['H'] }}</b> · Sakit <b>{{ $s['S'] }}</b> · Izin <b>{{ $s['I'] }}</b> · Alpa <b>{{ $s['A'] }}</b></span>
        <span>Info 7 hari: <b>{{ $m['posts'] }}</b></span>
        <span>Dijemput <b>{{ $row['dijemput'] }}</b> · menunggu <b>{{ $row['menunggu']->count() }}</b>
          @if ($row['terlambat'] > 0) <span class="tag wait">{{ $row['terlambat'] }} terlambat</span> @endif
        </span>
        <span>Catatan pribadi ditandatangani: <b>{{ $m['signed'] }}/{{ $m['ind'] }}</b></span>
      </div>
      <div class="small muted" style="margin-top:8px">Info massal dibaca {{ $m['read'] }}% orang tua</div>
      <div class="bar"><i style="width:{{ $m['read'] }}%"></i></div>
    </div>
  @endforeach
@endsection
