@extends('layouts.app')

@section('title', 'Beranda · '.auth()->user()->name)

@section('content')
  @php
      $statusLabel = ['H' => 'Hadir', 'S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa'];
  @endphp

  {{-- ===== alarm keselamatan ===== --}}
  @if ($alert && $alert->ack_at === null)
    <div class="hero alarm" role="alert">
      <h3>ALARM: {{ $child->name }} belum tiba di sekolah</h3>
      <p>Dicatat {{ $guru?->name }} pukul {{ App\Support\Tgl::jam($alert->alarm_at) }} —
        tidak hadir dan belum ada keterangan. Mohon segera konfirmasi.</p>

      <form method="POST" action="{{ route('ortu.safety.ack') }}">
        @csrf
        <label class="f" for="sfReason" style="margin-top:0">{{ $child->name }} aman bersama saya karena</label>
        <select id="sfReason" name="reason">
          <option value="S">Sakit</option>
          <option value="I">Izin / keperluan keluarga</option>
          <option value="T">Terlambat, sedang menuju sekolah</option>
        </select>
        <label class="f" for="sfNote">Keterangan</label>
        <input type="text" id="sfNote" name="note" maxlength="100" placeholder="mis. Demam, istirahat di rumah" required>
        <button class="btn full" type="submit" style="margin-top:12px">Matikan alarm &amp; kabari sekolah</button>
      </form>

      <form method="POST" action="{{ route('ortu.safety.sos') }}">
        @csrf
        <button class="btn line full" type="submit" style="margin-top:8px">
          Saya tidak tahu — anak seharusnya sudah di sekolah
        </button>
      </form>

      <div style="text-align:center;margin-top:6px">
        @if ($alert->muted)
          <span class="small">Suara disenyapkan · alarm tetap aktif sampai Anda merespons</span>
        @else
          <form method="POST" action="{{ route('ortu.safety.mute') }}">
            @csrf
            <button class="mini" type="submit">Senyapkan suara</button>
          </form>
        @endif
      </div>
    </div>
  @elseif ($alert && $alert->ack_type === 'darurat')
    <div class="hero telat" role="alert">
      <h3>Sekolah sudah diberi tahu</h3>
      <p style="margin-bottom:0">Laporan darurat terkirim pukul {{ App\Support\Tgl::jam($alert->ack_at) }}
        ke {{ $guru?->name }} dan pimpinan. Segera telepon kantor sekolah.</p>
    </div>
  @elseif ($alert)
    @php
        $reasons = ['S' => 'Sakit', 'I' => 'Izin', 'T' => 'Terlambat, sedang menuju sekolah'];
    @endphp
    <div class="card">
      <span class="tag ok">✓ Sekolah sudah dikabari {{ App\Support\Tgl::jam($alert->ack_at) }}</span>
      <p style="margin:8px 0 0">
        {{ $child->name }} aman — {{ $reasons[$alert->ack_reason] ?? '' }}@if ($alert->ack_note) · {{ $alert->ack_note }} @endif
      </p>
    </div>
  @endif

  {{-- ===== penjemputan ===== --}}
  @if ($pickup->status === 'siap')
    @php $late = $pickup->reminded_at !== null; @endphp
    <div class="hero {{ $late ? 'telat' : 'siap' }}">
      <h3>{{ $late ? 'Pengingat: '.$child->name.' belum dijemput' : $child->name.' sudah bisa dijemput' }}</h3>
      <p>Kelas selesai pukul {{ App\Support\Tgl::jam($pickup->ready_at) }} ·
        menunggu <b data-since="{{ $pickup->ready_at->getTimestamp() * 1000 }}"></b></p>
      @if ($pickup->otw)
        <b>✓ Guru sudah tahu Anda sedang di jalan</b>
      @else
        <form method="POST" action="{{ route('ortu.otw') }}">
          @csrf
          <button class="btn" type="submit">Saya sedang di jalan</button>
        </form>
      @endif
    </div>
  @elseif ($pickup->status === 'dijemput')
    <div class="hero calm">
      <h3>{{ $child->name }} sudah dijemput</h3>
      <p style="margin-bottom:0">Dijemput oleh <b>{{ $pickup->picked_by }}</b>
        pukul {{ App\Support\Tgl::jam($pickup->picked_at) }} · dicatat {{ $guru?->name }}.</p>
    </div>
  @endif

  {{-- ===== kartu anak ===== --}}
  <div class="card trow">
    <div class="avatar">{{ $child->initials }}</div>
    <div style="flex:1">
      <b>{{ $child->name }}</b>
      <div class="small muted">{{ $child->class->name }} · {{ $guru?->name }}</div>
    </div>
    <div style="text-align:right">
      @if ($hadir)
        <span class="pill {{ $hadir }}" style="width:auto;padding:0 10px">{{ $statusLabel[$hadir] }}</span>
      @else
        <span class="tag">Belum diisi</span>
      @endif
      <div class="small muted">kehadiran hari ini</div>
    </div>
  </div>

  {{-- ===== wajib dibaca ===== --}}
  @if ($wajib->isNotEmpty())
    <div class="sec"><h3>Wajib dibaca</h3><span>{{ $wajib->count() }} catatan pribadi</span></div>
    @foreach ($wajib as $post)
      <x-post-card :post="$post" ctx="ortu" />
    @endforeach
  @endif

  {{-- ===== info hari ini ===== --}}
  <div class="sec"><h3>Info hari ini</h3><span>{{ App\Support\Tgl::pendek(today()) }}</span></div>
  @if ($infoHariIni->isNotEmpty())
    @foreach ($infoHariIni as $post)
      <x-post-card :post="$post" ctx="ortu" />
    @endforeach
  @else
    <div class="card muted">Belum ada info baru hari ini.</div>
  @endif

  <div class="note">
    Tidak ada chat di sini. Semua info tersimpan dan bisa dibaca kapan saja, jadi guru tidak perlu siaga 24 jam.
    Untuk hal mendesak, hubungi kantor sekolah.
  </div>
@endsection
