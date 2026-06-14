<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Notifications\Listeners\SendClubWelcomeEmail;
use App\Domains\Tenancy\Events\ClubRegistered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Maps domain events (under app/Domains/<Context>/Events) to their listeners.
 * Listeners live in app/Domains/<Context>/Listeners and are queued; the event
 * catalog is documented in docs/events/event-catalog.md.
 *
 * @var array<class-string, list<class-string>>
 */
class DomainEventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, list<class-string>>
     */
    private array $listen = [
        ClubRegistered::class => [
            SendClubWelcomeEmail::class,
        ],
    ];

    public function boot(): void
    {
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }
}
