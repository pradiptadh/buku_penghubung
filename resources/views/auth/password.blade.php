@extends('layouts.app')

@section('title', 'Ganti password')

@section('content')
  <div class="sec"><h3>Ganti password</h3><span>akun ini saja</span></div>

  @if ($errors->any())
    <div class="errors">
      @foreach ($errors->all() as $error)
        {{ $error }}<br>
      @endforeach
    </div>
  @endif

  <div class="card">
    <form method="POST" action="{{ route('password.update') }}">
      @csrf
      <label class="f" for="old" style="margin-top:0">Password lama</label>
      <input type="password" id="old" name="old" required autocomplete="current-password">

      <label class="f" for="new">Password baru (min. 6 karakter)</label>
      <input type="password" id="new" name="new" required autocomplete="new-password">

      <label class="f" for="confirm">Ulangi password baru</label>
      <input type="password" id="confirm" name="confirm" required autocomplete="new-password">

      <div style="display:flex;gap:8px;margin-top:12px">
        <a class="btn ghost" href="{{ route(auth()->user()->homeRoute()) }}">Kembali</a>
        <button class="btn" type="submit" style="flex:1">Simpan password</button>
      </div>
    </form>
  </div>

  <div class="note">Lupa password? Minta admin sekolah untuk meresetnya ke password bawaan.</div>
@endsection
