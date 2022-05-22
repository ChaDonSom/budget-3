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
        return [PusherChannel::class];
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
        $title = "Transaction for {$names} posted";
        return PusherMessage::create()
                    ->web()
                    ->sound('success')
                    ->setOption('icon', config('app.url') . '/android-chrome-192x192.png')
                    ->title($title)
                    ->body("$count account{$s} affected. $sum total difference. Make sure to confirm it goes through with your bank.")
                    ->link(($this->url ?? config('app.url')) . '/#/batch-updates/' . $this->batchUpdate->id);
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
}
