@extends('layouts.app')

@section('title', 'Buat info · '.auth()->user()->name)

@section('content')
  <div class="sec"><h3>Buat info baru</h3><span>{{ $class->name }}</span></div>

  @if ($errors->any())
    <div class="errors">
      @foreach ($errors->all() as $error)
        {{ $error }}<br>
      @endforeach
    </div>
  @endif

  <div class="card">
    <form method="POST" action="{{ route('guru.info.store') }}">
      @csrf

      <label class="f" for="target" style="margin-top:0">Kirim ke</label>
      <select id="target" name="target">
        <option value="all">Semua orang tua {{ $class->name }}</option>
        @foreach ($class->students as $s)
          <option value="{{ $s->id }}" @selected(old('target') === (string) $s->id)>
            Pribadi — orang tua {{ $s->name }}
          </option>
        @endforeach
      </select>
      <div class="note" style="margin:8px 0 0">
        Catatan pribadi <b>wajib ditandatangani</b> orang tua sebagai bukti sudah dibaca.
      </div>

      <div class="grid2">
        <div>
          <label class="f" for="periode">Periode</label>
          <select id="periode" name="periode">
            @foreach (App\Http\Controllers\PostController::PERIODE as $p)
              <option value="{{ $p }}" @selected(old('periode') === $p)>{{ ucfirst($p) }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="f" for="kategori">Kategori</label>
          <select id="kategori" name="kategori">
            @foreach (App\Http\Controllers\PostController::KATEGORI as $k)
              <option value="{{ $k }}" @selected(old('kategori') === $k)>{{ $k }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <label class="f" for="judul">Judul</label>
      <input type="text" id="judul" name="judul" maxlength="80" value="{{ old('judul') }}"
             placeholder="mis. Kegiatan hari ini" required>

      <label class="f" for="isi">Isi info</label>
      <textarea id="isi" name="isi" placeholder="Tulis kegiatan, pembelajaran, atau pengumuman…">{{ old('isi') }}</textarea>

      <div style="display:flex;gap:8px;margin-top:12px">
        <a class="btn ghost" href="{{ route('guru.info') }}">Batal</a>
        <button class="btn" type="submit" style="flex:1">Kirim</button>
      </div>
    </form>
  </div>
@endsection
