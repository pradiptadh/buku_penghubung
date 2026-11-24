<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Buku Penghubung Digital')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body data-poll-url="{{ route('poll') }}">
<div id="app" class="role-{{ auth()->user()->role }}">
  <header class="top">
    <div class="line1">
      <span class="brand">BUKU PENGHUBUNG</span>
      <div class="hbtns">
        <a class="out" href="{{ route('password.edit') }}">Password</a>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="out" type="submit">Keluar</button>
        </form>
      </div>
    </div>
    <h2>{{ $shell['title'] }}</h2>
    <div class="sub">{{ $shell['sub'] }}</div>
  </header>

  <main>@yield('content')</main>

  @if (count($shell['tabs']) > 0)
    <nav class="tabs">
      @foreach ($shell['tabs'] as $tab)
        <a href="{{ $tab['url'] }}" class="{{ $tab['current'] ? 'on' : '' }}"@if ($tab['current']) aria-current="page"@endif>
          <x-icon :name="$tab['icon']" />{{ $tab['label'] }}
          @if (($shell['badges'][$tab['key']] ?? 0) > 0)
            <span class="dot">{{ $shell['badges'][$tab['key']] }}</span>
          @endif
        </a>
      @endforeach
    </nav>
  @endif
</div>
<div id="toast" role="status" aria-live="polite">{{ session('toast') }}</div>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
