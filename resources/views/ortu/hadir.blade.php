@extends('layouts.app')

@section('title', 'Kehadiran · '.auth()->user()->name)

@section('content')
  @php
      $statusLabel = ['H' => 'Hadir', 'S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa'];
  @endphp

  <div class="sec"><h3>Riwayat kehadiran</h3><span>{{ $child->name }}</span></div>

  <div class="card">
    @foreach (array_reverse($days) as $d)
      @php $v = $byDate[$d]->status ?? null; @endphp
      <div class="stu">
        <div class="nm"><b>{{ App\Support\Tgl::panjang($d) }}</b></div>
        @if ($v)
          <span class="pill {{ $v }}" style="width:auto;padding:0 10px">{{ $statusLabel[$v] }}</span>
        @else
          <span class="tag">Belum diisi</span>
        @endif
      </div>
    @endforeach
  </div>

  <div class="sec"><h3>Lapor tidak masuk</h3><span>hari ini</span></div>

  @if ($report)
    <div class="card">
      <span class="tag ok">✓ Terkirim {{ App\Support\Tgl::jam($report->reported_at) }}</span>
      <p style="margin:8px 0 0"><b>{{ $statusLabel[$report->type] }}</b> — {{ $report->note }}</p>
      <p class="small muted" style="margin:4px 0 0">Guru melihat laporan ini saat mengisi kehadiran.</p>
    </div>
  @else
    @if ($errors->any())
      <div class="errors">
        @foreach ($errors->all() as $error)
          {{ $error }}<br>
        @endforeach
      </div>
    @endif
    <div class="card">
      <form method="POST" action="{{ route('ortu.absence') }}">
        @csrf
        <label class="f" for="abType" style="margin-top:0">Alasan</label>
        <select id="abType" name="type">
          <option value="S">Sakit</option>
          <option value="I">Izin</option>
        </select>
        <label class="f" for="abNote">Keterangan</label>
        <input type="text" id="abNote" name="note" maxlength="100" placeholder="mis. Demam, periksa ke dokter" required>
        <button class="btn full" type="submit" style="margin-top:12px">Kirim ke guru</button>
      </form>
    </div>
  @endif
@endsection
