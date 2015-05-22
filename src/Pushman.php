<?php namespace Pushman\PHPLib;

use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use Pushman\PHPLib\Exceptions\InvalidChannelException;
use Pushman\PHPLib\Exceptions\InvalidConfigException;
use Pushman\PHPLib\Exceptions\InvalidEventException;

class Pushman {

    private static $url = 'http://live.pushman.dfl.mn';
    private $privateKey;
    private $guzzle;
    private $config;

    public function __construct($privateKey, array $config = [], Client $guzzle = null)
    {
        $this->privateKey = $privateKey;
        $this->guzzle = $guzzle;
        $this->config = $config;

        $this->validatePrivatekey($privateKey);
        $this->initializeGuzzle();
        $this->initializeConfig();
    }

    public function push($event, $channel = 'public', array $payload = [])
    {
        $payload = $this->preparePayload($payload);
        $this->validateEvent($event);
        $channels = $this->validateChannel($channel);

        $url = $this->getURL();

        $headers = [
            'body' => [
                'private'  => $this->privateKey,
                'channels' => $channels,
                'event'    => $event,
                'payload'  => $payload
            ]
        ];

        $response = $this->processRequest($url, $headers);

        return $response;
    }

    public function channel($channel)
    {
        $channel = $this->validateChannel($channel, false);

        $url = $this->getURL('channel');

        $headers = [
            'body' => [
                'private' => $this->privateKey,
                'channel' => $channel
            ]
        ];

        $response = $this->processRequest($url, $headers, 'get');

        return $response;
    }

    public function token($channel)
    {
        $channel = $this->channel($channel);

        return ['token' => $channel['public'], 'expires' => $channel['token_expires']];
    }

    public function channels()
    {
        $url = $this->getURL('channels');

        $headers = [
            'body' => [
                'private' => $this->privateKey
            ]
        ];

        $response = $this->processRequest($url, $headers, 'get');

        return $response;
    }

    private function processRequest($url, $headers, $method = 'post')
    {
        if ($method == 'post') {
            $response = $this->guzzle->post($url, $headers);
        } else {
            $params = $this->processGetParams($headers);
            $response = $this->guzzle->get($url . $params);
        }
        $response = $this->processResponse($response);

        return $response;
    }

    private function processGetParams($headers)
    {
        $paramStrings = [];
        foreach ($headers['body'] as $key => $value) {
            $paramStrings[] = $key . "=" . $value;
        }
        $paramString = "?";
        $paramString .= implode("&", $paramStrings);

        return $paramString;
    }

    private function initializeGuzzle()
    {
        if (is_null($this->guzzle)) {
            $this->guzzle = new Client();
        }
    }

    private function initializeConfig()
    {
        if (empty($this->config['url'])) {
            $this->config['url'] = static::$url;
        }

        $this->config['url'] = rtrim($this->config['url'], '/');

        if (filter_var($this->config['url'], FILTER_VALIDATE_URL) === false) {
            throw new InvalidConfigException('You must provide a valid URL in the config.');
        }
    }

    private function preparePayload($payload)
    {
        $payload = json_encode($payload);

        return $payload;
    }

    private function getURL($endpoint = null)
    {
        if (is_null($endpoint)) {
            $endpoint = $this->getEndpoint();
        } else {
            $endpoint = '/api/' . $endpoint;
        }

        return $this->config['url'] . $endpoint;
    }

    private function getEndpoint()
    {
        return '/api/push';
    }

    private function processResponse(Response $response)
    {
        $response = $response->getBody()->getContents();
        $response = json_decode($response, true);

        return $response;
    }

    private function validateEvent($event)
    {
        if (empty($event)) {
            throw new InvalidEventException('You must provide an event name.');
        }

        if (strpos($event, ' ') !== false) {
            throw new InvalidEventException('No spaces are allowed in event names.');
        }
    }

    private function validatePrivatekey($private)
    {
        if (strlen($private) !== 60) {
            throw new InvalidConfigException('This cannot possibly be a valid private key.');
        }
    }

    private function validateChannel($channels = [], $returnAsArray = true)
    {
        if ($returnAsArray) {
            if (is_string($channels)) {
                $channels = [$channels];
            }
            if (empty($channels)) {
                return ['public'];
            }
            foreach ($channels as $channel) {
                if (strpos($channel, ' ') !== false) {
                    throw new InvalidChannelException('No spaces are allowed in channel names.');
                }
            }

            return json_encode($channels);
        } else {
            if (empty($channels)) {
                return 'public';
            }
            if (strpos($channels, ' ') !== false) {
                throw new InvalidChannelException('No spaces are allowed in channel names.');
            }

            return $channels;
        }
    }
}