<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Salinan email untuk kabar penting (jadwal bimbingan dan sidang, revisi laporan, berkas koreksi, nilai, keputusan KP).
 * Dikirim terpisah dari lonceng (KpNotice) oleh Notify::send(..., email: true) supaya gangguan SMTP tidak menggagalkan aksi pengguna.
 * Selama MAIL_MAILER=log, email hanya tercatat di storage/logs.
 */
class KpEmail extends Notification
{
    public function __construct(
        public string $title,
        public ?string $body = null,
        public ?string $url = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title.' · '.config('app.name'))
            ->greeting('Halo, '.($notifiable->name ?? ''))
            ->line($this->title);

        if ($this->body) {
            $mail->line($this->body);
        }
        if ($this->url) {
            $mail->action('Buka SIMAGTI', $this->url);
        }

        return $mail->salutation('Salam, SIMAGTI');
    }
}
