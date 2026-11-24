@extends('layouts.app')

@section('title', 'Admin sekolah')

@section('content')
  <div class="note">
    Reset mengembalikan password akun ke bawaan (<b>guru123</b>, <b>ortu123</b>, <b>pimpinan123</b>).
    Admin tidak bisa melihat password siapa pun.
  </div>

  <div class="sec"><h3>Pimpinan</h3><span>{{ $pimpinan->count() }} akun</span></div>
  <div class="card">
    @foreach ($pimpinan as $u)
      @include('admin._row', ['user' => $u, 'sub' => $u->position ?? 'Pimpinan'])
    @endforeach
  </div>

  <div class="sec"><h3>Guru</h3><span>{{ $guru->count() }} akun</span></div>
  <div class="card">
    @foreach ($guru as $u)
      @include('admin._row', ['user' => $u, 'sub' => $u->teacher?->class?->name ?? 'Guru'])
    @endforeach
  </div>

  @foreach ($ortuPerKelas as $class)
    @php $accounts = $class->students->map->parentUser->filter(); @endphp
    <div class="sec"><h3>Orang tua · {{ $class->name }}</h3><span>{{ $accounts->count() }} akun</span></div>
    <div class="card">
      @foreach ($accounts as $u)
        @include('admin._row', ['user' => $u, 'sub' => 'Orang tua'])
      @endforeach
    </div>
  @endforeach
@endsection
