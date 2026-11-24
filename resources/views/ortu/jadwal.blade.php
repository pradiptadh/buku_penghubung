@extends('layouts.app')

@section('title', 'Jadwal · '.auth()->user()->name)

@section('content')
  @php
      $todayDow = today()->isWeekday() ? today()->dayOfWeekIso : 1;
  @endphp

  <div class="sec"><h3>Jadwal {{ $class->name }}</h3>
    <span>Pulang {{ $slots->isNotEmpty() ? $slots->last()->jamLabel() : '–' }}</span>
  </div>

  <div class="chips">
    @for ($d = 1; $d <= 5; $d++)
      <a class="chip {{ $d === $day ? 'on' : '' }}" href="{{ route('ortu.jadwal', ['day' => $d]) }}">
        {{ App\Support\Tgl::HARI[$d] }}{{ $d === $todayDow ? ' · hari ini' : '' }}
      </a>
    @endfor
  </div>

  <div class="card">
    @if ($slots->isNotEmpty())
      <div class="sched">
        @foreach ($slots as $slot)
          <div class="t">{{ $slot->jamLabel() }}</div>
          <div>{{ $slot->activity }}</div>
        @endforeach
      </div>
    @else
      <div class="muted">Belum ada jadwal untuk hari ini.</div>
    @endif
  </div>
@endsection
