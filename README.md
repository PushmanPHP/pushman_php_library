# pushman_php
The Pushman PHP Library

[![Latest Stable Version](https://poser.pugx.org/pushman/phplib/v/stable)](https://packagist.org/packages/pushman/phplib) [![Total Downloads](https://poser.pugx.org/pushman/phplib/downloads)](https://packagist.org/packages/pushman/phplib) [![Latest Unstable Version](https://poser.pugx.org/pushman/phplib/v/unstable)](https://packagist.org/packages/pushman/phplib) [![License](https://poser.pugx.org/pushman/phplib/license)](https://packagist.org/packages/pushman/phplib)

## Installation
```php
composer require pushman/phplib
```

## Usage
```php
use Pushman\PHPLib\Pushman;
$pushman = new Pushman('private-key-goes-here');

$response = $pushman->push('kittens_are_cute', 'public', ['foo' => 'asdasdasdasd']);
```

As of version 2.1.0 in Pushman, you can push to multiple channels by feeding an array into the channels variable.

```php
use Pushman\PHPLib\Pushman;
$pushman = new Pushman('private-key-goes-here');

$response = $pushman->push('kittens_are_cute', ['public', 'auth'], ['foo' => 'asdasdasdasd']);
```

On your own pushman instance:

```php
use Pushman\PHPLib\Pushman;
$pushman = new Pushman('private-key-goes-here, ['url' => 'http://pushman.yoursite.com']);

$response = $pushman->push('kittens_are_cute', 'public', ['foo' => 'asdasdasdasd']);
```

`$response` will always return a JSON payload with `status` and `message` along with any other relevant information about your event.

### Getting Information

Because Pushman can generate your public token every 60 minutes, updating your clients should be an automatic process. You can use the following code to grab the public token of any channel.

```php
use Pushman\PHPLib\Pushman;
$pushman = new Pushman('private-key-goes-here');

$response = $pushman->token('public');
$token = $response['token'];
```

And you can load all channel information by the `channels()` and `channel()` method.

```php
use Pushman\PHPLib\Pushman;
$pushman = new Pushman('private-key-goes-here');

$response = $pushman->channel('auth');
$max_connections = $response['max_connections'];

$response = $pushman->channels();
foreach($response as $channel) {
	echo("Found channel {$channel['name']}.\n");
}
```