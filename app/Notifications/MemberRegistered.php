<?php

namespace App\Notifications;

use App\Models\Community;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberRegistered extends Notification
{
    public function __construct(
        public Community $community,
        public string $password,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param  User  $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Welcome to :community', ['community' => $this->community->name]))
            ->greeting(__('Hello, :name!', ['name' => $notifiable->name]))
            ->line(__('You have been registered as a member of :community.', ['community' => $this->community->name]))
            ->line(__('Your temporary password is: :password', ['password' => $this->password]))
            ->line(__('Please change your password after your first login.'))
            ->action(__('Login'), url('/login'));
    }
}
