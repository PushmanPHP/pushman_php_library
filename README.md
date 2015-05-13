# pushman_php
The Pushman PHP Library

## Installation
```php
composer require pushman/phplib
```

## Usage
```php
use Pushman\PHPLib\Pushman;

$pushman = new Pushman('private-key-goes-here');

$response = $pushman->push('kittens_are_cute', ['foo' => 'asdasdasdasd']);
```

`$response` will always return a JSON payload with `status` and `message`.
