<?php

namespace spec\Pushman\PHPLib;

use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PushmanSpec extends ObjectBehavior {

    function let()
    {
        $this->beConstructedWith('{PRIVATE_KEY_HERE}', ['url' => 'http://live.pushman.dfl.mn']);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Pushman\PHPLib\Pushman');
    }

    function it_should_grab_a_channel_token()
    {
        $tokenTest = $this->token('public');
        $tokenTest->shouldbeArray();
        $tokenTest->shouldHaveKey('token');
        $tokenTest->shouldHaveCount(2);
    }

    function it_should_return_an_array_of_channels()
    {
        $channelTest = $this->channels();
        $channelTest->shouldBeArray();
        $channelTest->shouldHaveCount(2);
    }

    function it_should_return_a_single_channels_information()
    {
        $channelTest = $this->channel('public');
        $channelTest->shouldBeArray();
        $channelTest->shouldHaveKey('name');
        $channelTest->shouldHaveKey('public');
        $channelTest->shouldHaveKey('id');
        $channelTest->shouldHaveCount(9);
    }

    function it_should_push_an_event_to_pushman()
    {
        $eventPush = $this->push('test_event');
        $eventPush->shouldBeArray();
        $eventPush->shouldHaveCount(7);
        #$eventPush->shouldHaveKeyWithValue('status', 'value');
    }

    function it_should_not_allow_for_blank_event_names()
    {
        $this->shouldThrow('Pushman\PHPLib\Exceptions\InvalidEventException')->duringPush('');
    }

    function it_should_not_allow_spaces_in_event_names()
    {
        $this->shouldThrow('Pushman\PHPLib\Exceptions\InvalidEventException')->duringPush('my event');
    }

    function it_should_now_allow_spaces_in_channel_names()
    {
        $this->shouldThrow('Pushman\PHPLib\Exceptions\InvalidChannelException')->duringPush('validEventName', 'a channel');
    }

    private function buildMockResponses()
    {
        $channelsMockResponse = new Response(200);
        $channelsMockResponseBody = Stream::factory(fopen(__DIR__ . '/../../../tests/channels.txt', 'r+'));
        $channelsMockResponse->setBody($channelsMockResponseBody);

        $channelMockResponse = new Response(200);
        $channelMockResponseBody = Stream::factory(fopen(__DIR__ . '/../../../tests/channel.txt', 'r+'));
        $channelMockResponse->setBody($channelMockResponseBody);

        $pushMockResponse = new Response(200);
        $pushMockResponseBody = Stream::factory(fopen(__DIR__ . '/../../../tests/push.txt', 'r+'));
        $pushMockResponse->setBody($pushMockResponseBody);

        $mock = new Mock([
            $pushMockResponse
        ]);

        return $mock;
    }
}
