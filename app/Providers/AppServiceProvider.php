<?php

namespace App\Providers;

use App\Mail\Transport\PhpMailTransport;
use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;
use App\Observers\TimelineEventObserver;
use App\Observers\TimelineLineObserver;
use App\Observers\WorldTimelineVisualObserver;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('php_mail', function (): PhpMailTransport {
            return new PhpMailTransport;
        });

        URL::forceScheme('https');

        TimelineLine::observe(TimelineLineObserver::class);
        TimelineEvent::observe(TimelineEventObserver::class);
        World::observe(WorldTimelineVisualObserver::class);
    }
}
