<?php

namespace App\Notifications;

use App\Mail\YourMailableName;
use App\Models\YourModel;
use App\Models\UserBase;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * [Description of this notification]
 *
 * @example
 * $user->notify(new YourNotificationName($model));
 */
class YourNotificationName extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private YourModel $model
    ) {
    }

    /**
     * Determine notification delivery channels
     *
     * @param UserBase $notifiable
     * @return array<int, string>
     */
    public function via(UserBase $notifiable): array
    {
        if (!($notifiable instanceof User)) {
            return [];
        }

        $exclude = [];

        // Check if email is verified
        if (is_null($notifiable->email_verified_at)) {
            $exclude[] = 'mail';
        }
        // Check if dummy email
        elseif ($notifiable->isDummyEmail) {
            $exclude[] = 'mail';
        }
        // Check user notification preferences
        elseif (!$notifiable->setting->email['your_notification_type']) {
            $exclude[] = 'mail';
        }

        return array_diff(['mail'], $exclude);
    }

    /**
     * Build mail content
     *
     * @param User $notifiable
     * @return YourMailableName
     */
    public function toMail(User $notifiable): YourMailableName
    {
        return (new YourMailableName($this->model))
            ->to($notifiable->email);
    }
}
