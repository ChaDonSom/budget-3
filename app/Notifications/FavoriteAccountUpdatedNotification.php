<?php

namespace App\Notifications;

use App\Models\Account;
use App\Models\AccountBatchUpdate;
use Cknow\Money\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\PusherPushNotifications\PusherChannel;
use NotificationChannels\PusherPushNotifications\PusherMessage;
use Illuminate\Support\Str;

class FavoriteAccountUpdatedNotification extends Notification
{
    use Queueable;

    /**
     * 
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        public AccountBatchUpdate $batchUpdate,
        public Account $account,
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
        $diff = Money::USD($this->account->pivot->amount);
        $total = Money::USD($this->account->amount);
        return PusherMessage::create()
                    ->web()
                    ->sound('success')
                    ->setOption('icon', config('app.url') . '/android-chrome-192x192.png')
                    ->title("Favorite account {$this->account->name} modified")
                    ->body("$diff total difference, for a new total of {$total}.")
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
