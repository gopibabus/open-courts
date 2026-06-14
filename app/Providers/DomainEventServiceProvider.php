<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Events\DiscoverEvents;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Auto-discovers domain event listeners across every bounded context.
 *
 * Any class in app/Domains/<Context>/Listeners with a `handle(SomeEvent $e)` method is
 * wired to that event automatically — no central registration needed. This keeps the
 * event wiring append-only: a new slice just drops a listener file. See the event
 * catalog in docs/events/event-catalog.md.
 */
class DomainEventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach ((array) glob(app_path('Domains/*/Listeners'), GLOB_ONLYDIR) as $listenerDir) {
            foreach (DiscoverEvents::within($listenerDir, base_path()) as $event => $listeners) {
                foreach ($listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }
}
