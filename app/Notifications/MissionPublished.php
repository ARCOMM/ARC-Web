<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\Discord\DiscordChannel;
use NotificationChannels\Discord\DiscordMessage;
use App\Models\Missions\Mission;
use App\Models\Portal\User;

class MissionPublished extends Notification
{
    use Queueable;

    /**
     * The mission instance.
     *
     * @var App\Models\Missions\Mission
     */
    protected $mission;

    protected $only_discord;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Mission $mission, bool $only_discord = false)
    {
        $this->mission = $mission;
        $this->only_discord = $only_discord;

        return $this;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return $this->only_discord ? [DiscordChannel::class] : ['database'];
    }

    /**
     * Discord message.
     *
     * @return any
     */
    public function toDiscord($notifiable)
    {
        return DiscordMessage::create("**{$this->mission->user->username}** submitted a mission named **{$this->mission->display_name}** {$this->mission->url()}");
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
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
            'mission' => $this->mission
        ];
    }

    /**
     * Gets the computed full URL for the notification.
     *
     * @return string
     */
    public static function link($notification)
    {
        $mission = Mission::findOrFail($notification->data['mission']['id']);

        return $mission->url();
    }

    /**
     * Gets the computed text for the notification.
     *
     * @return string
     */
    public static function text($notification)
    {
        $mission = Mission::findOrFail($notification->data['mission']['id']);

        return "{$mission->user->username} submitted their mission {$mission->display_name}";
    }

    /**
     * Gets the full icon URL for the notification.
     *
     * @return string
     */
    public static function icon($notification)
    {
        $mission = Mission::findOrFail($notification->data['mission']['id']);

        return $mission->user->avatar;
    }

    /**
     * Checks whether the notification still exists.
     *
     * @return boolean
     */
    public static function exists($notification)
    {
        $mission = Mission::find($notification->data['mission']['id']);

        if ($mission && $mission->user) return true;

        return false;
    }
}
