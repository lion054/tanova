<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorApiRateLimitWarning extends Notification
{
    public function __construct(
        private readonly string $keyName,
        private readonly int    $used,
        private readonly int    $limit,
        private readonly int    $percent,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $remaining = $this->limit - $this->used;

        return (new MailMessage)
            ->subject("[Tsoka] API rate limit at {$this->percent}% — {$this->keyName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your API key **{$this->keyName}** has used **{$this->used} of {$this->limit}** requests this month ({$this->percent}%).")
            ->line("You have **{$remaining} requests remaining** before the limit resets at the start of next month.")
            ->action('Manage API Keys', url('/vendor/portal/api-keys'))
            ->line('Upgrade your plan to increase your monthly request limit.');
    }
}
