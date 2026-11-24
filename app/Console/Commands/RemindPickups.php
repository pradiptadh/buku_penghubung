<?php

namespace App\Console\Commands;

use App\Models\Pickup;
use App\Models\Setting;
use App\Notifications\PickupReminderNotification;
use Illuminate\Console\Command;

/**
 * Pengingat penjemputan — paritas tick() prototipe:
 * status "siap" melewati ambang menit → pengingat ke ortu.
 */
class RemindPickups extends Command
{
    protected $signature = 'pickup:remind';

    protected $description = 'Pengingat otomatis untuk anak yang belum dijemput';

    public function handle(): int
    {
        $minutes = (int) Setting::get('remind_minutes', '15');

        Pickup::with('student.parentUser')
            ->whereDate('date', today())
            ->where('status', 'siap')
            ->whereNull('reminded_at')
            ->where('ready_at', '<=', now()->copy()->subMinutes($minutes))
            ->get()
            ->each(function (Pickup $pickup) {
                $pickup->update(['reminded_at' => now()]);
                $pickup->student->parentUser?->notify(new PickupReminderNotification($pickup));
            });

        return self::SUCCESS;
    }
}
