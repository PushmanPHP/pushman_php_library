<?php namespace Pushman\PHPLib;

use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use Pushman\PHPLib\Exceptions\InvalidConfigException;
use Pushman\PHPLib\Exceptions\InvalidEventException;

class Pushman {

    private $privateKey;
    private $guzzle;
    private $config;
    private $version = 0;

    public function __construct($privateKey, array $config = [], Client $guzzle = null)
    {
        $this->privateKey = $privateKey;
        $this->guzzle = $guzzle;
        $this->config = $config;

        $this->validatePrivatekey($privateKey);
        $this->initializeGuzzle();
        $this->initializeConfig();
    }

    public function push($type, array $payload = [])
    {
        $payload = $this->preparePayload($payload);
        $this->validateType($type);

        $url = $this->getURL();

        $response = $this->guzzle->post($url, [
            'body' => [
                'private' => $this->privateKey,
                'type'    => $type,
                'payload' => $payload
            ]
        ]);

        $response = $this->processResponse($response);

        return $response;
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
            $this->config['url'] = 'http://pushman.dfl.mn';
        }

        $this->config['url'] = rtrim($this->config['url'], '/');

        if (filter_var($this->config['url'], FILTER_VALIDATE_URL) === false) {
            throw new InvalidConfigException('You must provide a valid URL in the config.');
        }
    }

    private function validateType($type)
    {
        if (empty($type)) {
            throw new InvalidEventException('You must provide an event name.');
        }

        if (strpos($type, ' ') !== false) {
            throw new InvalidEventException('No spaces are allowed in event names.');
        }
    }

    private function preparePayload($payload)
    {
        $payload = json_encode($payload);

        return $payload;
    }

    private function getURL()
    {
        return $this->config['url'] . $this->getEndpoint();
    }

    private function getEndpoint()
    {
        return '/api/v' . $this->version . '/push';
    }

    private function processResponse(Response $response)
    {
        $response = $response->getBody()->getContents();
        $response = json_decode($response, true);

        return $response;
    }

    private function validatePrivatekey($private)
    {
        if (strlen($private) !== 60) {
            throw new InvalidConfigException('This cannot possibly be a valid private key.');
        }
    }
}