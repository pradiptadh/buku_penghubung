@extends('layouts.app')

@section('title', 'Jadwal · '.auth()->user()->name)

@section('content')
  @php
      $pulang = $slots->isNotEmpty() ? $slots->last()->jamLabel() : '–';
      $todayDow = today()->isWeekday() ? today()->dayOfWeekIso : 1;
  @endphp

  <div class="sec"><h3>Jadwal {{ $class->name }}</h3><span>Pulang {{ $pulang }}</span></div>

  <div class="chips">
    @for ($d = 1; $d <= 5; $d++)
      <a class="chip {{ $d === $day ? 'on' : '' }}"
         href="{{ route('guru.jadwal', array_filter(['day' => $d, 'edit' => $editing ? 1 : null])) }}">
        {{ App\Support\Tgl::HARI[$d] }}{{ $d === $todayDow ? ' · hari ini' : '' }}
      </a>
    @endfor
  </div>

  @if ($editing)
    <div class="card">
      <div class="small muted" style="margin-bottom:8px">
        Edit jadwal hari {{ App\Support\Tgl::HARI[$day] }}. Baris terakhir dianggap jam pulang.
      </div>

      <form method="POST" action="{{ route('guru.jadwal.store', $day) }}">
        @csrf
        @foreach ($slots as $slot)
          <div class="jrow">
            <input type="time" name="jam[]" value="{{ substr($slot->start_time, 0, 5) }}" aria-label="Jam">
            <input type="text" name="kegiatan[]" maxlength="80" value="{{ $slot->activity }}" placeholder="Kegiatan" aria-label="Kegiatan">
            <button class="x" type="button" data-del-row aria-label="Hapus baris">×</button>
          </div>
        @endforeach

        @if ($slots->isEmpty())
          <div class="jrow">
            <input type="time" name="jam[]" value="" aria-label="Jam">
            <input type="text" name="kegiatan[]" maxlength="80" value="" placeholder="Kegiatan" aria-label="Kegiatan">
            <button class="x" type="button" data-del-row aria-label="Hapus baris">×</button>
          </div>
        @endif

        <div class="jtpl">
          <div class="jrow">
            <input type="time" name="jam[]" value="" aria-label="Jam">
            <input type="text" name="kegiatan[]" maxlength="80" value="" placeholder="Kegiatan" aria-label="Kegiatan">
            <button class="x" type="button" data-del-row aria-label="Hapus baris">×</button>
          </div>
        </div>

        <button class="btn ghost sm" type="button" data-add-row>+ Tambah baris</button>

        <div style="display:flex;gap:8px;margin-top:12px">
          <a class="btn ghost" href="{{ route('guru.jadwal', ['day' => $day]) }}">Batal</a>
          <button class="btn" type="submit" style="flex:1">Simpan jadwal</button>
        </div>
      </form>
    </div>
  @else
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

    <a class="btn ghost full" style="display:block;text-align:center"
       href="{{ route('guru.jadwal', ['day' => $day, 'edit' => 1]) }}">
      Edit jadwal hari {{ App\Support\Tgl::HARI[$day] }}
    </a>
  @endif
@endsection
