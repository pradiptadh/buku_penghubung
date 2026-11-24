/* ============================================================
   Buku Penghubung Digital — interaktivitas ringan pengganti
   render loop prototipe: toast, counter "menunggu", tanda tangan,
   bintang penilaian, baris jadwal, beep alarm, dan polling.
   ============================================================ */
(function () {
  'use strict';

  /* ---------- toast (paritas toast()) ---------- */
  var toastEl = document.getElementById('toast');
  if (toastEl && toastEl.textContent.trim()) {
    toastEl.classList.add('show');
    setTimeout(function () { toastEl.classList.remove('show'); }, 2600);
  }

  /* ---------- counter "menunggu X mnt" (paritas fmtDur) ---------- */
  function fmtDur(ms) {
    var s = Math.max(0, Math.floor(ms / 1000));
    return s < 60 ? s + ' dtk' : Math.floor(s / 60) + ' mnt';
  }
  function tickCounters() {
    document.querySelectorAll('[data-since]').forEach(function (el) {
      el.textContent = fmtDur(Date.now() - Number(el.dataset.since));
    });
  }
  tickCounters();
  setInterval(tickCounters, 1000);

  /* ---------- tanda tangan: centang dulu, tombol aktif ---------- */
  document.querySelectorAll('[data-chk]').forEach(function (chk) {
    chk.addEventListener('change', function () {
      var btn = document.querySelector('[data-sign="' + chk.dataset.chk + '"]');
      if (btn) btn.disabled = !chk.checked;
    });
  });

  /* ---------- bintang penilaian (radio + label) ---------- */
  document.querySelectorAll('.stars').forEach(function (group) {
    var labels = group.querySelectorAll('label');
    function paint(active) {
      labels.forEach(function (label, i) { label.classList.toggle('on', i < active); });
    }
    group.querySelectorAll('input').forEach(function (input, i) {
      input.addEventListener('change', function () { paint(i + 1); });
    });
  });

  /* ---------- jadwal: tambah/hapus baris (delegasi agar baris baru ikut) ---------- */
  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-add-row]');
    if (add) {
      var tpl = add.closest('form')?.querySelector('.jtpl');
      if (tpl && tpl.firstElementChild) {
        tpl.parentNode.insertBefore(tpl.firstElementChild.cloneNode(true), tpl);
      }
      return;
    }
    var del = e.target.closest('[data-del-row]');
    if (del) {
      var row = del.closest('.jrow');
      if (row) row.remove();
    }
  });

  /* ---------- beep alarm (paritas ensureAudio/beep) ---------- */
  var AC = null;
  function ensureAudio() {
    try {
      AC = AC || new (window.AudioContext || window.webkitAudioContext)();
      if (AC.state === 'suspended') AC.resume();
    } catch (e) { /* abaikan */ }
  }
  function beep() {
    if (!AC) return;
    try {
      [0, 0.22].forEach(function (d) {
        var o = AC.createOscillator(), g = AC.createGain();
        o.type = 'square'; o.frequency.value = 880; g.gain.value = 0.07;
        o.connect(g); g.connect(AC.destination);
        o.start(AC.currentTime + d); o.stop(AC.currentTime + d + 0.15);
      });
    } catch (e) { /* abaikan */ }
  }
  document.addEventListener('click', ensureAudio, { once: true });

  /* ---------- polling (paritas tick) ---------- */
  var POLL_URL = document.body.dataset.pollUrl;
  var lastState = null;
  if (!POLL_URL) return;

  function typing() {
    var el = document.activeElement;
    return el && /INPUT|TEXTAREA|SELECT/.test(el.tagName);
  }

  setInterval(function () {
    fetch(POLL_URL, { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) {
        if (!d) return;
        if (d.alarm_active && !d.alarm_muted) beep();
        if (lastState && d.state && d.state !== lastState && !typing()) {
          location.reload();
        }
        lastState = d.state;
      })
      .catch(function () { /* diam */ });
  }, 10000);
})();
