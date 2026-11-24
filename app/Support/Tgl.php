<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Format tanggal & jam Bahasa Indonesia — paritas helper prototipe.
 */
class Tgl
{
    public const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    public const BULAN = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    /** "Senin, 17 November 2026" */
    public static function panjang(CarbonInterface|string $date): string
    {
        $d = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        return self::HARI[$d->dayOfWeek].', '.$d->day.' '.self::BULAN[$d->month - 1].' '.$d->year;
    }

    /** "Sen 17/11" */
    public static function pendek(CarbonInterface|string $date): string
    {
        $d = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        return mb_substr(self::HARI[$d->dayOfWeek], 0, 3).' '.$d->day.'/'.$d->month;
    }

    /** "08.15" */
    public static function jam(CarbonInterface|string $time): string
    {
        $t = $time instanceof CarbonInterface ? $time : Carbon::parse($time);

        return $t->format('H.i');
    }

    /** "Hari ini, 08.15" atau "Sen 17/11, 08.15" */
    public static function kapan(CarbonInterface|string $time): string
    {
        $t = $time instanceof CarbonInterface ? $time : carbon($time);
        $label = $t->isToday() ? 'Hari ini' : self::pendek($t);

        return $label.', '.self::jam($t);
    }

    /** "07.30" dari nilai time "07:30:00" */
    public static function jamLabel(string $hhmm): string
    {
        return str_replace(':', '.', substr($hhmm, 0, 5));
    }
}
