<?php

namespace App\Events;

use App\Models\Account;
use App\Models\AccountBatchUpdate;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountBatchUpdateSaved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public AccountBatchUpdate $update,
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
        return collect([$this->update->user])
            ->concat($this->update->user->sharedUsers)
            ->concat($this->update->user->usersWhoSharedToMe)
            ->map(fn(User $user) => (
                new PrivateChannel("AccountBatchUpdates.User.{$user->id}")
            ))->toArray();
    }

    public function broadcastWith() {
        return [
            'update' => AccountBatchUpdate::query()
                // Specifically ensure the batchUpdates on each of this one's accounts are only the not-done ones
                ->with('accounts')
                ->find($this->update->id)
        ];
    }
}
