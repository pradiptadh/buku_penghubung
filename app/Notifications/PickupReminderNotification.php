<?php

namespace App\Notifications;

use App\Models\Pickup;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PickupReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Buat instance notifikasi baru.
     */
    public function __construct(public Pickup $pickup)
    {
        //
    }

    /**
     * @param  User  $notifiable
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @param  User  $notifiable
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Pengingat: '.$this->pickup->student->name.' belum dijemput',
            'student' => $this->pickup->student->name,
            'ready_at' => $this->pickup->ready_at?->toIso8601String(),
        ];
    }

    /**
     * @param  User  $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pengingat: '.$this->pickup->student->name.' belum dijemput')
            ->line($this->pickup->student->name.' sudah siap dijemput sejak '.$this->pickup->ready_at?->format('H.i').'.');
    }
}
