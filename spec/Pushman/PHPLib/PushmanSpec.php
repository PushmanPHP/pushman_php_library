<?php namespace spec\Pushman\PHPLib;

use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PushmanSpec extends ObjectBehavior {

    /**
     * Contructor, Pushman needs a private key. This one is fake obviously.
     * URL isn't needed here.
     */
    function let()
    {
        $this->beConstructedWith('an_example_private_key_that_is_60_characters_long_exactly___', ['url' => 'http://live.pushman.dfl.mn']);
    }

    /**
     * Is the client initializable?
     */
    function it_is_initializable()
    {
        $this->shouldHaveType('Pushman\PHPLib\Pushman');
    }

    /**
     * Can it grab a channel token using a private key?
     */
    function it_should_grab_a_channel_token()
    {
        $client = $this->buildMockClient('channel');
        $this->setClient($client);

        $tokenTest = $this->token('public');
        $tokenTest->shouldbeArray();
        $tokenTest->shouldHaveKey('token');
        $tokenTest->shouldHaveCount(2);
    }

    /**
     * Can it return an array of channels?
     */
    function it_should_return_an_array_of_channels()
    {
        $client = $this->buildMockClient('channels');
        $this->setClient($client);

        $channelTest = $this->channels();
        $channelTest->shouldBeArray();
        $channelTest->shouldHaveCount(2);
    }

    /**
     * Can it return a single channels information?
     */
    function it_should_return_a_single_channels_information()
    {
        $client = $this->buildMockClient('channel');
        $this->setClient($client);

        $channelTest = $this->channel('public');
        $channelTest->shouldBeArray();
        $channelTest->shouldHaveKey('name');
        $channelTest->shouldHaveKey('public');
        $channelTest->shouldHaveKey('id');
        $channelTest->shouldHaveCount(9);
    }

    /**
     * Can it push an event to Pushman successfully?
     */
    function it_should_push_an_event_to_pushman()
    {
        $client = $this->buildMockClient('push');
        $this->setClient($client);

        $eventPush = $this->push('test_event');
        $eventPush->shouldBeArray();
        $eventPush->shouldHaveCount(7);
        $eventPush->shouldHaveKeyWithValue('status', 'success');
    }

    /**
     * Make sure it stops you entering fake names.
     */
    function it_should_not_allow_for_blank_event_names()
    {
        $this->shouldThrow('Pushman\PHPLib\Exceptions\InvalidEventException')->duringPush('');
    }

    /**
     * Make sure it stops spaces in event names.
     */
    function it_should_not_allow_spaces_in_event_names()
    {
        $this->shouldThrow('Pushman\PHPLib\Exceptions\InvalidEventException')->duringPush('my event');
    }

    /**
     * It shouldn't allow spaces in channel names.
     */
    function it_should_now_allow_spaces_in_channel_names()
    {
        $this->shouldThrow('Pushman\PHPLib\Exceptions\InvalidChannelException')->duringPush('validEventName', 'a channel');
    }

    /**
     * Build a fake guzzle client so we don't actually send requests.
     *
     * @param $textfile
     * @return \GuzzleHttp\Client
     */
    private function buildMockClient($textfile)
    {
        $client = new Client();

        $mockResponse = new Response(200);
        $mockResponseBody = Stream::factory(fopen(__DIR__ . '/../../../tests/' . $textfile . '.txt', 'r+'));
        $mockResponse->setBody($mockResponseBody);

        $mock = new Mock();
        $mock->addResponse($mockResponse);

        $client->getEmitter()->attach($mock);

        return $client;
    }
}
