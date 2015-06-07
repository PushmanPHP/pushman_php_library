<?php namespace Pushman\PHPLib;

use Illuminate\Support\ServiceProvider;
use Pushman\PHPLib\Pushman;
use Pushman\PHPLib\PushmanBroadcaster;

class PushmanServiceProvider extends ServiceProvider {

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        app('Illuminate\Broadcasting\BroadcastManager')->extend(
            'pushman',
            function ($app) {
                return new PushmanBroadcaster(
                    app('pushman')
                );
            }
        );
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('pushman', function($app) {
            $private = env('PUSHMAN_PRIVATE');
            $url = env('PUSHMAN_URL', 'http://live.pushman.dfl.mn');

            return new Pushman($private, ['url' => $url]);
        });
    }
}
