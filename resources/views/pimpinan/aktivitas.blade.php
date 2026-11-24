@extends('layouts.app')

@section('title', 'Aktivitas · '.auth()->user()->name)

@section('content')
  @php
      $items = ['semua' => 'Semua kelas'] + $classes->mapWithKeys(fn ($c) => [$c->id => $c->name])->all();
  @endphp

  <x-chips :items="$items" :current="$filter" :base="route('pimpinan.aktivitas')" param="kelas" />

  @forelse ($posts as $post)
    <x-post-card :post="$post" ctx="pimpinan" />
  @empty
    <div class="empty">Belum ada aktivitas.</div>
  @endforelse
@endsection
