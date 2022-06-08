<?php

namespace App\Events;

use App\Models\User;
use App\Notifications\AccountBatchUpdateHandledNotification;
use App\Notifications\FavoriteAccountUpdatedNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class PushNotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public User $user,
        public string $uuid,
        public string $title,
        public string $message,
        public string $action,
    )
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel("App.Models.User.{$this->user->id}");
    }

    public function broadcastWith()
    {
        return [
            'notification' => [
                'data' => [
                    'uuid' => $this->uuid,
                    'title' => $this->title,
                    'message' => $this->message,
                    'action' => $this->action,
                ]
            ]
        ];
    }
}
