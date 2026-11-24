@props(['alert'])
@php
    $reasons = ['S' => 'Sakit', 'I' => 'Izin', 'T' => 'Terlambat, sedang menuju sekolah'];
@endphp
@if ($alert && $alert->ack_at === null)
    <span class="small" style="color:var(--red);font-weight:800">
        Alarm terkirim ke orang tua {{ App\Support\Tgl::jam($alert->alarm_at) }} · belum ada respons
    </span>
@elseif ($alert && $alert->ack_type === 'darurat')
    <span class="small" style="color:var(--red);font-weight:800">
        DARURAT {{ App\Support\Tgl::jam($alert->ack_at) }}: {{ $alert->ackBy?->name }} tidak tahu keberadaan anak — hubungi sekarang
    </span>
@elseif ($alert)
    <span class="small" style="color:#256b41;font-weight:700">
        {{ $alert->ackBy?->name }} ({{ App\Support\Tgl::jam($alert->ack_at) }}): anak aman — {{ $reasons[$alert->ack_reason] ?? '' }}@if ($alert->ack_note) · {{ $alert->ack_note }} @endif
    </span>
@endif
