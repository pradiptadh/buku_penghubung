@extends('layouts.app')

@section('title', 'Info · '.auth()->user()->name)

@section('content')
  @php
      $items = ['semua' => 'Semua', 'harian' => 'Harian', 'mingguan' => 'Mingguan', 'bulanan' => 'Bulanan', 'tahunan' => 'Tahunan', 'pribadi' => 'Pribadi'];
  @endphp

  <a class="btn full" href="{{ route('guru.info.create') }}"
     style="display:block;text-align:center;margin-bottom:12px">+ Buat info baru</a>

  <x-chips :items="$items" :current="$filter" :base="route('guru.info')" />

  @forelse ($posts as $post)
    <x-post-card :post="$post" ctx="guru" />
  @empty
    <div class="empty">Belum ada info di kategori ini.</div>
  @endforelse
@endsection
