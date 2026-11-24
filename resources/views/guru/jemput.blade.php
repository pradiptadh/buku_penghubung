@extends('layouts.app')

@section('title', 'Penjemputan · '.auth()->user()->name)

@section('content')
  @php
      $waiting = $present->filter(fn ($s) => ($pickups[$s->id] ?? null)?->status !== 'siap'
          && ($pickups[$s->id] ?? null)?->status !== 'dijemput')->count();
  @endphp

  <div class="sec"><h3>Penjemputan</h3><span>Jam pulang {{ $jamPulang }}</span></div>

  <div class="card">
    <form method="POST" action="{{ route('guru.jemput.remind') }}">
      @csrf
      <label class="f" for="remind" style="margin-top:0">Pengingat otomatis jika belum dijemput setelah</label>
      <div style="display:flex;gap:8px">
        <select id="remind" name="remind_minutes" style="flex:1">
          <option value="15" @selected($remindMinutes === 15)>15 menit</option>
          <option value="30" @selected($remindMinutes === 30)>30 menit</option>
          <option value="60" @selected($remindMinutes === 60)>60 menit</option>
        </select>
        <button class="btn ghost sm" type="submit">Simpan</button>
      </div>
    </form>
  </div>

  @if ($present->isEmpty())
    <div class="empty">Belum ada anak berstatus Hadir. Isi kehadiran terlebih dahulu.</div>
  @else
    @if ($waiting > 0 && $editId === null)
      <form method="POST" action="{{ route('guru.jemput.all') }}">
        @csrf
        <button class="btn full" type="submit" style="margin-bottom:12px">
          Kelas selesai — beri tahu semua orang tua ({{ $waiting }})
        </button>
      </form>
    @endif

    <div class="card">
      @foreach ($present as $s)
        @php $p = $pickups[$s->id] ?? null; @endphp

        @if ($editId == $s->id)
          <div class="editbox">
            <b>{{ $s->name }}</b>
            <form method="POST" action="{{ route('guru.jemput.update', $s) }}">
              @csrf
              <div class="grid2">
                <div>
                  <label class="f" for="pkSt-{{ $s->id }}">Status</label>
                  <select id="pkSt-{{ $s->id }}" name="status">
                    <option value="belajar" @selected($p?->status === 'belajar' || $p === null)>Masih di kelas</option>
                    <option value="siap" @selected($p?->status === 'siap')>Siap dijemput</option>
                    <option value="dijemput" @selected($p?->status === 'dijemput')>Sudah dijemput</option>
                  </select>
                </div>
                <div>
                  <label class="f" for="pkJam-{{ $s->id }}">Jam</label>
                  <input type="time" id="pkJam-{{ $s->id }}" name="jam"
                         value="{{ $p?->ready_at?->format('H:i') ?? $p?->picked_at?->format('H:i') ?? now()->format('H:i') }}">
                </div>
              </div>
              <label class="f" for="pkBy-{{ $s->id }}">Dijemput oleh</label>
              <input type="text" id="pkBy-{{ $s->id }}" name="oleh" maxlength="60"
                     placeholder="mis. Nenek, Pak Budi (sopir)" value="{{ $p?->picked_by ?? $s->parentUser?->name }}">
              <div style="display:flex;gap:8px;margin-top:10px">
                <a class="btn ghost sm" href="{{ route('guru.jemput') }}">Batal</a>
                <button class="btn sm" type="submit" style="flex:1">Simpan</button>
              </div>
            </form>
          </div>
        @else
          <div class="pk">
            <div class="avatar">{{ $s->initials }}</div>
            <div class="nm">
              <b>{{ $s->name }}</b><br>
              @if ($p === null)
                <span class="small muted">Masih di kelas</span>
              @elseif ($p->status === 'siap')
                <span class="small">
                  Siap sejak {{ App\Support\Tgl::jam($p->ready_at) }} ·
                  menunggu <b data-since="{{ $p->ready_at->getTimestamp() * 1000 }}"></b>
                  @if ($p->otw) · <span style="color:var(--green);font-weight:700">orang tua di jalan</span> @endif
                  @if ($p->reminded_at) <span class="tag wait">Pengingat terkirim {{ App\Support\Tgl::jam($p->reminded_at) }}</span> @endif
                </span>
              @else
                <span class="tag ok">✓ Dijemput {{ App\Support\Tgl::jam($p->picked_at) }}</span>
                <span class="small">oleh <b>{{ $p->picked_by }}</b></span>
              @endif
            </div>
            <div class="pkbtns">
              @if ($p === null)
                <form method="POST" action="{{ route('guru.jemput.ready', $s) }}">
                  @csrf
                  <button class="btn sm ghost" type="submit">Siap dijemput</button>
                </form>
              @elseif ($p->status === 'siap')
                <form method="POST" action="{{ route('guru.jemput.picked', $s) }}">
                  @csrf
                  <button class="btn sm" type="submit">Sudah dijemput</button>
                </form>
              @endif
              <a class="mini" href="{{ route('guru.jemput', ['edit' => $s->id]) }}">Ubah</a>
            </div>
          </div>
        @endif
      @endforeach
    </div>
  @endif
@endsection
