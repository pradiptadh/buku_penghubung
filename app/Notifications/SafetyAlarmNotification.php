<?php

namespace App\Notifications;

use App\Models\SafetyAlert;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SafetyAlarmNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Buat instance notifikasi baru.
     */
    public function __construct(public SafetyAlert $alert, public bool $emergency = false)
    {
        //
    }

    /**
     * @param  User  $notifiable
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @param  User  $notifiable
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->emergency
                ? 'DARURAT: orang tua tidak tahu keberadaan anak'
                : 'Alarm: '.$this->alert->student->name.' belum tiba di sekolah',
            'student' => $this->alert->student->name,
            'class' => $this->alert->student->class->name,
            'emergency' => $this->emergency,
        ];
    }

    /**
     * @param  User  $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        $student = $this->alert->student->name;

        return $this->emergency
            ? (new MailMessage)
                ->subject('DARURAT: '.$student)
                ->line('Orang tua melaporkan tidak tahu keberadaan '.$student.' yang seharusnya sudah di sekolah.')
                ->line('Segera hubungi orang tua dan kantor sekolah.')
            : (new MailMessage)
                ->subject('Alarm: '.$student.' belum tiba di sekolah')
                ->line($student.' dicatat tidak hadir tanpa keterangan dan sudah melewati jam masuk.')
                ->line('Mohon segera konfirmasi melalui aplikasi.');
    }
}
