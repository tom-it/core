<?php namespace Flarum\Core\Notifications\Listeners;

use Flarum\Events\DiscussionWasRenamed;
use Flarum\Core\Posts\DiscussionRenamedPost;
use Flarum\Core\Notifications\DiscussionRenamedBlueprint;
use Flarum\Core\Notifications\NotificationSyncer;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionRenamedNotifier
{
    /**
     * @var NotificationSyncer
     */
    protected $notifications;

    /**
     * @param NotificationSyncer $notifications
     */
    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(DiscussionWasRenamed::class, __CLASS__.'@whenDiscussionWasRenamed');
    }

    /**
     * @param \Flarum\Events\DiscussionWasRenamed $event
     */
    public function whenDiscussionWasRenamed(DiscussionWasRenamed $event)
    {
        $post = DiscussionRenamedPost::reply(
            $event->discussion->id,
            $event->actor->id,
            $event->oldTitle,
            $event->discussion->title
        );

        $post = $event->discussion->mergePost($post);

        if ($event->discussion->start_user_id !== $event->actor->id) {
            $blueprint = new DiscussionRenamedBlueprint($post);

            if ($post->exists) {
                $this->notifications->sync($blueprint, [$event->discussion->startUser]);
            } else {
                $this->notifications->delete($blueprint);
            }
        }
    }
}
