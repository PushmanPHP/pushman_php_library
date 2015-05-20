<?php namespace Pushman\PHPLib;

use Illuminate\Contracts\Broadcasting\Broadcaster;
use Pushman\PHPLib\Pushman;

class PushmanBroadcaster implements Broadcaster {

    protected $pushman;

    public function __construct(Pushman $pushman)
    {
        $this->pushman = $pushman;
    }

    /**
     * Broadcast the given event.
     *
     * @param  array  $channels
     * @param  string $event
     * @param  array  $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $this->pushman->push($event, $channels, $payload);
    }
}