<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk · Buku Penghubung Digital</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div id="app">
  <div class="welcome">
    <div class="rings"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>

    <div class="cover">
      <small>Playgroup &amp; Kindy</small>
      <h1>Buku Penghubung Digital</h1>
      <p>Satu tempat untuk kehadiran, kegiatan, jadwal, dan penjemputan — tanpa grup WhatsApp.</p>
    </div>

    <h2 class="pick-title">Masuk</h2>

    @if ($errors->any())
      <div class="errors">
        @foreach ($errors->all() as $error)
          {{ $error }}<br>
        @endforeach
      </div>
    @endif

    <div class="rolecard">
      <form method="POST" action="{{ route('login.attempt') }}">
        @csrf
        <label class="f" for="email" style="margin-top:0">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">

        <label class="f" for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">

        <button class="btn full" type="submit" style="margin-top:14px">Masuk</button>
      </form>
    </div>

    <p class="foot">
      Akun contoh: <b>ms.rina@sekolah.test</b> · <b>mama.aruna@sekolah.test</b> · <b>pimpinan1@sekolah.test</b> · <b>admin@sekolah.test</b><br>
      Password bawaan: <b>guru123</b> · <b>ortu123</b> · <b>pimpinan123</b> · <b>admin123</b>
    </p>
  </div>
</div>
<div id="toast" role="status" aria-live="polite">{{ session('toast') }}</div>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
