# pushman_php
The Pushman PHP Library

[![Latest Stable Version](https://poser.pugx.org/pushman/phplib/v/stable)](https://packagist.org/packages/pushman/phplib) [![Build Status](https://travis-ci.org/PushmanPHP/pushman_php_library.svg?branch=master)](https://travis-ci.org/PushmanPHP/pushman_php_library) [![Total Downloads](https://poser.pugx.org/pushman/phplib/downloads)](https://packagist.org/packages/pushman/phplib) [![Latest Unstable Version](https://poser.pugx.org/pushman/phplib/v/unstable)](https://packagist.org/packages/pushman/phplib) [![License](https://poser.pugx.org/pushman/phplib/license)](https://packagist.org/packages/pushman/phplib)

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

#### Extending Laravel
You can also extend Laravels `event()` functionality by including the Pushman ServiceProvider in your `config/app.php` file. Add the service provider `Pushman\PHPLib\PushmanServiceProvider` in your app.php file and then in your `.env` file, add the following keys:

```
PUSHMAN_PRIVATE=60_char_private_token_here
PUSHMAN_URL=http://pushman.yoursite.com
```

Later in your `config/broadcasting.php` file, add in under `connections` the Pushman settings:

```php
'pushman' => [
    'driver'  => 'pushman',
    'private' => env('PUSHMAN_PRIVATE'),
    'url'     => env('PUSHMAN_URL'),
],
```

From that point onwards, you can use `php artisan make:event {Name}` to make a Laravel Event, inside that event implement `ShouldBroadcast`, and in your `broadcastOn` function, return an array of channels you want to broadcast on.

##### Example
`php artisan make:event UserCreated` - Called when a user is created.
```php
$user = new User([
	'name' => 'James Duffleman',
	'email' => 'george@duffleman.co.uk',
	'password' => bcrypt('aPassword')
]);

event(new UserCreated($user));
```

```php
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UserCreated extends Event implements ShouldBroadcast {

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }
    
    public function broadcastOn()
    {
        return ['public'];
    }
}
```

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