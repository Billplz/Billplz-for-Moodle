# Billplz API Class
[![Latest Stable Version](https://poser.pugx.org/billplz/billplz-api/version)](https://packagist.org/packages/billplz/billplz-api)
[![Total Downloads](https://poser.pugx.org/billplz/billplz-api/downloads)](https://packagist.org/packages/billplz/billplz-api)
[![Latest Unstable Version](https://poser.pugx.org/billplz/billplz-api/v/unstable)](//packagist.org/packages/billplz/billplz-api)
[![License](https://poser.pugx.org/billplz/billplz-api/license)](https://packagist.org/packages/billplz/billplz-api)

## Minimum System Requirement
- PHP 7.0 or newer

## Installation

To install through composer, simply execute:

```bash
composer require billplz/billplz-api guzzlehttp/guzzle
```

or

```json
{
    "require": {
        "billplz/billplz-api": "^3.7",
        "guzzlehttp/guzzle": "~6.0"
    }
}
```

## Usages

### Connecting with Billplz API

By it's design, the Connect class are able to determine the API Key is belong to Production or Staging environment.

```php
<?php

use Billplz\Connect;
$connnect = (new Connect('4e49de80-1670-4606-84f8-2f1d33a38670'))->detectMode();

// Or manually set the mode
$connnect = new Connect('4e49de80-1670-4606-84f8-2f1d33a38670');
$connect->setMode(true);
```

## Other

Please open an issue or email to Facebook: [Billplz Dev Jam](https://www.facebook.com/groups/billplzdevjam/)
