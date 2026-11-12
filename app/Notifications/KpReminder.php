<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Pengingat otomatis (perintah kp:pengingat). Masuk ke lonceng notifikasi dan email.
 * Selama MAIL_MAILER=log, email hanya tercatat di storage/logs; setelah SMTP hosting disetel, langsung terkirim.
 * Kunci ($key) dipakai untuk mencegah pengingat yang sama dikirim berulang.
 */
class KpReminder extends KpNotice
{
    public function __construct(
        public string $key,
        string $title,
        ?string $body = null,
        ?string $url = null,
        string $icon = 'clock',
        string $tone = 'amber',
    ) {
        parent::__construct($title, $body, $url, $icon, $tone);
    }

    public function via(object $notifiable): array
    {
        return filled($notifiable->email ?? null) ? ['database', 'mail'] : ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return parent::toArray($notifiable) + ['key' => $this->key, 'reminder' => true];
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

        return $mail->salutation('Pengingat otomatis SIMAGTI');
    }
}
