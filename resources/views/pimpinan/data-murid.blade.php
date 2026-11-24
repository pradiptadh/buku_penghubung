@extends('layouts.app')

@section('title', 'Data murid · '.auth()->user()->name)

@section('content')
  <div class="note">
    Registrasi murid dan orang tua/wali. Orang tua yang baru didaftarkan langsung punya akun
    dengan password bawaan <b>ortu123</b>.
  </div>

  @if ($errors->any())
    <div class="errors">
      @foreach ($errors->all() as $error)
        {{ $error }}<br>
      @endforeach
    </div>
  @endif

  @foreach ($classes as $class)
    @php
        $teacher = $class->teachers->first()?->user;
        $students = $class->students;
    @endphp
    <div class="sec"><h3>{{ $class->name }}</h3>
      <span>{{ $students->count() }} murid · {{ $teacher?->name }}</span>
    </div>

    <div class="card">
      @foreach ($students as $s)
        @if ($editId == $s->id)
          <div class="editbox">
            <form method="POST" action="{{ route('pimpinan.data.update', $s) }}">
              @csrf
              <label class="f" for="stName-{{ $s->id }}" style="margin-top:0">Nama murid</label>
              <input type="text" id="stName-{{ $s->id }}" name="name" maxlength="40" value="{{ $s->name }}" required>
              <label class="f" for="stParent-{{ $s->id }}">Nama orang tua / wali</label>
              <input type="text" id="stParent-{{ $s->id }}" name="parent" maxlength="60"
                     placeholder="mis. Mama Aruna / Ibu Sinta" value="{{ $s->parentUser?->name }}" required>
              <div style="display:flex;gap:8px;margin-top:10px">
                <a class="btn ghost sm" href="{{ route('pimpinan.data') }}">Batal</a>
                <button class="btn sm" type="submit" style="flex:1">Simpan</button>
              </div>
            </form>
          </div>
        @else
          <div class="stu">
            <div class="avatar">{{ $s->initials }}</div>
            <div class="nm">
              <b>{{ $s->name }}</b>
              <span class="small muted">{{ $s->parentUser?->name }}</span>
            </div>
            <a class="btn sm ghost" href="{{ route('pimpinan.data', ['edit' => $s->id]) }}">Edit</a>
          </div>
        @endif
      @endforeach

      @if ($newClass == $class->id)
        <div class="editbox">
          <form method="POST" action="{{ route('pimpinan.data.store') }}">
            @csrf
            <input type="hidden" name="class_id" value="{{ $class->id }}">
            <label class="f" for="newName-{{ $class->id }}" style="margin-top:0">Nama murid</label>
            <input type="text" id="newName-{{ $class->id }}" name="name" maxlength="40" value="{{ old('name') }}" required>
            <label class="f" for="newParent-{{ $class->id }}">Nama orang tua / wali</label>
            <input type="text" id="newParent-{{ $class->id }}" name="parent" maxlength="60"
                   placeholder="mis. Mama Aruna / Ibu Sinta" value="{{ old('parent') }}" required>
            <label class="f" for="newEmail-{{ $class->id }}">Email akun orang tua (opsional)</label>
            <input type="email" id="newEmail-{{ $class->id }}" name="parent_email"
                   placeholder="otomatis dari nama bila dikosongkan">
            <div style="display:flex;gap:8px;margin-top:10px">
              <a class="btn ghost sm" href="{{ route('pimpinan.data') }}">Batal</a>
              <button class="btn sm" type="submit" style="flex:1">Daftarkan</button>
            </div>
          </form>
        </div>
      @else
        <a class="btn ghost full sm" style="display:block;text-align:center;margin-top:8px"
           href="{{ route('pimpinan.data', ['new' => $class->id]) }}">+ Daftarkan murid baru</a>
      @endif
    </div>
  @endforeach
@endsection
