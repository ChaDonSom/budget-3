<?php

namespace App\Notifications;

use App\Models\AccountBatchUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\PusherPushNotifications\PusherChannel;
use NotificationChannels\PusherPushNotifications\PusherMessage;
use Illuminate\Support\Str;
use Cknow\Money\Money;

class AccountBatchUpdateHandledNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        public AccountBatchUpdate $batchUpdate,
        public Collection $accounts,
    )
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [ PusherChannel::class ];
    }

    /**
     * Get the push-notification representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toPushNotification($notifiable)
    {
        $sum = Money::USD($this->accounts->sum('pivot.amount'));
        $count = $this->accounts->count();
        $s = $count == 1 ? '' : 's';
        $names = Str::limit($this->accounts->map(fn($a) => $a->name)->join(', '), 100);
        $title = "Transaction posted for {$names}";
        return PusherMessage::create()
                    ->web()
                    ->sound('success')
                    ->setOption('icon', '/build/android-chrome-192x192.png')
                    ->setOption('badge', '/build/safari-pinned-tab.svg')
                    ->title($this->getTitle())
                    ->body($this->getMessage())
                    ->link($this->getAction())
                    ->setOption('web', [
                        'icon' => config('app.url') . '/build/android-chrome-192x192.png',
                        'hide_notification_if_site_has_focus' => true,
                        'deep_link' => $this->getAction(),
                        'badge' => config('app.url') . '/build/safari-pinned-tab.svg',
                    ])
                    ->setOption('webhookUrl', config('app.url') . '/beams/incoming');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    public function getTitle(): string
    {
        $names = Str::limit($this->accounts->map(fn($a) => $a->name)->join(', '), 100);
        $title = "Transaction posted for {$names}";
        return $title;
    }

    public function getMessage(): string
    {
        $sum = Money::USD($this->accounts->sum('pivot.amount'));
        $count = $this->accounts->count();
        $s = $count == 1 ? '' : 's';
        $message = "$count account{$s} affected. $sum total difference. Make sure to confirm it goes through with your bank, and schedule the next one if needed.";
        return $message;
    }

    public function getAction(): string
    {
        return config('app.url') . '/#/batch-updates/' . $this->batchUpdate->id;
    }
}
