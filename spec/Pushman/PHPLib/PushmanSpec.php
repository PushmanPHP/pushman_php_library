<?php namespace spec\Pushman\PHPLib;

use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Pushman\PHPLib\Pushman;

/**
 * Class PushmanSpec
 * @package spec\Pushman\PHPLib
 * @mixin Pushman
 */
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
     * It can build a new channel when given a string.
     */
    function you_can_build_a_new_channel()
    {
        $client = $this->buildMockClient('new_channel');
        $this->setClient($client);

        $channel = $this->buildChannel('my_channel');
        $channel->shouldBeArray();
        $channel->shouldHaveCount(1);
        $channel->shouldHaveKeyWithValue('name', 'my_channel');
    }

    /**
     * You can build a new set of channels by giving it an array of channels
     */
    function you_can_build_a_set_of_new_channels_by_using_an_array()
    {
        $client = $this->buildMockClient('new_channels');
        $this->setClient($client);

        $channels = $this->buildChannel(['my_chan1', 'my_chan2']);
        $channels->shouldBeArray();
        $channels->shouldHaveCount(2);
    }

    function it_can_destroy_a_channel()
    {
        $client = $this->buildMockClient('delete');
        $this->setClient($client);

        $res = $this->destroyChannel('my_channel');
        $res->shouldBeArray();
        $res->shouldHaveCount(4);
        $res->shouldHaveKeyWithValue('status', 'success');
        $res->shouldHaveKeyWithValue('deleted', 'my_channel');
    }

    function it_can_destroy_an_array_of_channels()
    {
        $client = $this->buildMockClient('delete_array');
        $this->setClient($client);

        $res = $this->destroyChannel(['my_channel', 'my_channel2', 'my_channel3']);
        $res->shouldHaveCount(4);
        $res->shouldHaveKeyWithValue('failed_on', '');
        $res->shouldHaveKeyWithvalue('status', 'success');
    }

    function it_can_destroy_some_of_that_array_but_not_all_of_it()
    {
        $client = $this->buildMockClient('delete_half');
        $this->setClient($client);

        $res = $this->destroyChannel(['my_channel', 'public']);
        $res->shouldHaveCount(4);
        $res->shouldHaveKeyWithValue('failed_on', 'public');
        $res->shouldHaveKeyWithvalue('status', 'success');
        $res->shouldHaveKeyWithvalue('deleted', 'my_channel');
    }

    function it_wont_destroy_the_public_channel()
    {
        $this->shouldThrow('Pushman\PHPLib\Exceptions\InvalidDeleteRequestException')->duringDestroyChannel('public');
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
