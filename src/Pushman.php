<?php namespace Pushman\PHPLib;

use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use Pushman\PHPLib\Exceptions\InvalidChannelException;
use Pushman\PHPLib\Exceptions\InvalidConfigException;
use Pushman\PHPLib\Exceptions\InvalidDeleteRequestException;
use Pushman\PHPLib\Exceptions\InvalidEventException;

class Pushman {

    /**
     * Our Pushman instance URL. Defaults to live.
     *
     * @var string
     */
    private static $url = 'http://live.pushman.dfl.mn';
    /**
     * The private key we initiated the class with.
     *
     * @var
     */
    private $privateKey;
    /**
     * Our Guzzle client
     *
     * @var \GuzzleHttp\Client
     */
    private $guzzle;
    /**
     * Config array
     *
     * @var array
     */
    private $config;

    /**
     * Initiating the class, setting up variables and configuration.
     *
     * @param                    $privateKey
     * @param array              $config
     * @param \GuzzleHttp\Client $guzzle
     * @throws \Pushman\PHPLib\Exceptions\InvalidConfigException
     */
    public function __construct($privateKey, array $config = [], Client $guzzle = null)
    {
        $this->privateKey = $privateKey;
        $this->guzzle = $guzzle;
        $this->config = $config;

        $this->validatePrivatekey($privateKey);
        $this->initializeGuzzle();
        $this->initializeConfig();
    }

    /**
     * Set the client after initialisation. Used only for testing.
     *
     * @param \GuzzleHttp\Client $client
     */
    public function setClient(Client $client)
    {
        $this->guzzle = $client;
    }

    /**
     * Push an event to Pushman, the most used command.
     *
     * @param        $event
     * @param string $channel
     * @param array  $payload
     * @return \GuzzleHttp\Message\Response|mixed|string|void
     * @throws \Pushman\PHPLib\Exceptions\InvalidChannelException
     * @throws \Pushman\PHPLib\Exceptions\InvalidEventException
     */
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

    /**
     * Get information on a single channel.
     *
     * @param $channel
     * @return \GuzzleHttp\Message\Response|mixed|string|void
     * @throws \Pushman\PHPLib\Exceptions\InvalidChannelException
     */
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

    /**
     * Get the token of a single channel.
     *
     * @param $channel
     * @return array
     */
    public function token($channel)
    {
        $channel = $this->channel($channel);

        return ['token' => $channel['public'], 'expires' => $channel['token_expires']];
    }

    /**
     * Get an array of channels in the site.
     *
     * @return \GuzzleHttp\Message\Response|mixed|string|void
     */
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

    /**
     * Build a new channel or set of channels.
     *
     * @param $channel
     * @return \GuzzleHttp\Message\Response|mixed|string|void
     * @throws \Pushman\PHPLib\Exceptions\InvalidChannelException
     */
    public function buildChannel($channel)
    {
        $channels = $this->validateChannel($channel);

        $url = $this->getURL('channel');

        $headers = [
            'body' => [
                'private' => $this->privateKey,
                'channel' => $channels
            ]
        ];

        $response = $this->processRequest($url, $headers, 'post');

        return $response;
    }

    /**
     * Destroy a channel or set of channels.
     *
     * @param $channel
     * @return \GuzzleHttp\Message\Response|mixed|string|void
     * @throws \Pushman\PHPLib\Exceptions\InvalidChannelException
     */
    public function destroyChannel($channel)
    {
        $channels = $this->validateChannel($channel);

        $arrayOfChannels = json_decode($channels, true);
        if (in_array('public', $arrayOfChannels)) {
            throw new InvalidDeleteRequestException('You cannot delete the public channel.');
        }

        $url = $this->getURL('channel');

        $headers = [
            'body' => [
                'private' => $this->privateKey,
                'channel' => $channels
            ]
        ];

        $response = $this->processRequest($url, $headers, 'delete');

        return $response;
    }

    /**
     * Process a request and return the handled response.
     *
     * @param        $url
     * @param        $headers
     * @param string $method
     * @return \GuzzleHttp\Message\Response|mixed|string|void
     */
    private function processRequest($url, $headers, $method = 'post')
    {
        if ($method == 'post') {
            $response = $this->guzzle->post($url, $headers);
        } elseif ($method == 'delete') {
            $response = $this->guzzle->delete($url, $headers);
        } else {
            $params = $this->processGetParams($headers);
            $response = $this->guzzle->get($url . $params);
        }
        $response = $this->processResponse($response);

        return $response;
    }

    /**
     * If we are doing a GET request, turn it into URL params.
     *
     * @param $headers
     * @return string
     */
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

    /**
     * Setup guzzle if a client hasn't been given.
     */
    private function initializeGuzzle()
    {
        if (is_null($this->guzzle)) {
            $this->guzzle = new Client();
        }
    }

    /**
     * Setup the config if one hasn't been provided.
     *
     * @throws \Pushman\PHPLib\Exceptions\InvalidConfigException
     */
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

    /**
     * Encode a payload
     *
     * @param $payload
     * @return string
     */
    private function preparePayload($payload)
    {
        $payload = json_encode($payload);

        return $payload;
    }

    /**
     * Get the URL of our Pushman instance.
     * Defaults to the live site Push command.
     *
     * @param null $endpoint
     * @return string
     */
    private function getURL($endpoint = null)
    {
        if (is_null($endpoint)) {
            $endpoint = $this->getEndpoint();
        } else {
            $endpoint = '/api/' . $endpoint;
        }

        return $this->config['url'] . $endpoint;
    }

    /**
     * Get the endpoint for the PUSH comment.
     *
     * @return string
     */
    private function getEndpoint()
    {
        return '/api/push';
    }

    /**
     * Process a repsonse, get the JSON and output the decoded values.
     *
     * @param \GuzzleHttp\Message\Response $response
     * @return \GuzzleHttp\Message\Response|mixed|string
     */
    private function processResponse(Response $response)
    {
        $response = $response->getBody()->getContents();
        $response = json_decode($response, true);

        return $response;
    }

    /**
     * Validate an event name.
     *
     * @param $event
     * @throws \Pushman\PHPLib\Exceptions\InvalidEventException
     */
    private function validateEvent($event)
    {
        if (empty($event)) {
            throw new InvalidEventException('You must provide an event name.');
        }

        if (strpos($event, ' ') !== false) {
            throw new InvalidEventException('No spaces are allowed in event names.');
        }
    }

    /**
     * Validate a private key.
     *
     * @param $private
     * @throws \Pushman\PHPLib\Exceptions\InvalidConfigException
     */
    private function validatePrivatekey($private)
    {
        if (strlen($private) !== 60) {
            throw new InvalidConfigException('This cannot possibly be a valid private key.');
        }
    }

    /**
     * Validate a channel or set of channels, return JSON if appropriate.
     *
     * @param array $channels
     * @param bool  $returnAsArray
     * @return array|string
     * @throws \Pushman\PHPLib\Exceptions\InvalidChannelException
     */
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