<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Kabar singkat di lonceng notifikasi (disimpan di tabel notifications).
 * Kabar penting juga dikirim lewat email oleh Notify::send(..., email: true) memakai KpEmail.
 */
class KpNotice extends Notification
{
    public function __construct(
        public string $title,
        public ?string $body = null,
        public ?string $url = null,
        public string $icon = 'mail',
        public string $tone = 'blue',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'icon' => $this->icon,
            'tone' => $this->tone,
        ];
    }
}
