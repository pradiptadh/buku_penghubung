@extends('layouts.app')

@section('title', 'Info · '.auth()->user()->name)

@section('content')
  @php
      $items = ['semua' => 'Semua', 'harian' => 'Harian', 'mingguan' => 'Mingguan', 'bulanan' => 'Bulanan', 'tahunan' => 'Tahunan', 'pribadi' => 'Pribadi'];
  @endphp

  <x-chips :items="$items" :current="$filter" :base="route('ortu.info')" />

  @forelse ($posts as $post)
    <x-post-card :post="$post" ctx="ortu" />
  @empty
    <div class="empty">Belum ada info di kategori ini.</div>
  @endforelse
@endsection
