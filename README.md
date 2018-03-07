# tol-api-php
[![Build Status](https://travis-ci.org/traderinteractive/tol-api-php.svg?branch=master)](https://travis-ci.org/traderinteractive/tol-api-php)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/traderinteractive/tol-api-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/traderinteractive/tol-api-php/?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/traderinteractive/tol-api-php/badge.svg?branch=master)](https://coveralls.io/github/traderinteractive/tol-api-php?branch=master)

[![Latest Stable Version](https://poser.pugx.org/traderinteractive/tol-api/v/stable)](https://packagist.org/packages/traderinteractive/tol-api)
[![Latest Unstable Version](https://poser.pugx.org/traderinteractive/tol-api/v/unstable)](https://packagist.org/packages/traderinteractive/tol-api)
[![License](https://poser.pugx.org/traderinteractive/tol-api/license)](https://packagist.org/packages/traderinteractive/tol-api)

[![Total Downloads](https://poser.pugx.org/traderinteractive/tol-api/downloads)](https://packagist.org/packages/traderinteractive/tol-api)
[![Daily Downloads](https://poser.pugx.org/traderinteractive/tol-api/d/daily)](https://packagist.org/packages/traderinteractive/tol-api)
[![Monthly Downloads](https://poser.pugx.org/traderinteractive/tol-api/d/monthly)](https://packagist.org/packages/traderinteractive/tol-api)

This is a PHP client for [REST](http://en.wikipedia.org/wiki/Representational_state_transfer) APIs like the TraderOnline APIs.

## Requirements

This api client requires PHP 7.0 or newer and uses composer to install further PHP dependencies.  See the [composer specification](composer.json) for more details.

When contributing, access to a working mongo database for testing is needed.  See the [Contribution Guidelines](.github/CONTRIBUTING.md) for more details.

## Installation

tol-api-php can be installed for use in your project using [composer](http://getcomposer.org).

The recommended way of using this library in your project is to add a `composer.json` file to your project.  The following contents would add tol-api-php as a dependency:

```sh
composer require traderinteractive/tol-api
```

## Basic Usage

The basic guzzle client, without caching or automated pagination handling is mostly easy to work with.

To instantiate a client, you need the guzzle adapter, client id, client secret, and API url.  This client should work with apis like the TOL APIs.
```php
use TraderInteractive\Api;
$apiAdapter = new Api\GuzzleAdapter();
$auth = Api\Authentication::createClientCredentials(
    'clientId',
    'clientSecret'
)
$apiClient = new Api\Client(
    $apiAdapter,
    $auth,
    'https://baseApiUrl/v1'
);
```

Then you can make index requests like below, although it is recommended to take a look at the [Collection](#collection) section below so that you can take advantage of automatic pagination handling.  Here's an example of how to fetch a single page of items.
```php
<li>
<?php
$response = $apiClient->index(
    'resourceName',
    array('aFilter' => '5')
);

if ($response->getStatusCode() !== 200) {
    throw new Exception('Non successful index call');
}

$body = json_decode($response->getBody(), true);
$total = $body['pagination']['total'];

// Loop over the first page of items
foreach ($body['result'] as $item) {
    echo "<li>{$item['foo']}</li>\n";
}
?>
</ul>
```

For getting just a single item back from the api, you can use the `get` method:
```php
// Get item 1234
$response = $apiClient->get('resourceName', '1234');

if ($response->getStatusCode() !== 200) {
    throw new Exception('Failed to fetch item 1234');
}

$item = json_decode($response->getBody(), true);
echo "Fetched item {$item['foo']}\n";
```

For creating a new item, you can use the `post` method:
```php
$response = $apiClient->post(
    'resourceName',
    array(
        'foo' => array(
            'bar' => 'boo',
            'bing' => '5',
        ),
    )
);

if ($response->getStatusCode() !== 201) {
    throw new Exception('Failed to create item foo');
}

$item = json_decode($response->getBody(), true);
echo $item['result']['foo'];
```

For updating an item, you can use the `put` method:
```php
// Set item 1234's foo to bar.
$response = $apiClient->put(
    'resourceName',
    '1234',
    array('bing' => array('foo' => 'bar'))
);

if ($response->getStatusCode() !== 200) {
    throw new Exception('Failed to update item 1234');
}
```

For deleting an item, you can use the `delete` method:
```php
// Delete item 1234.
$response = $apiClient->delete('resourceName', '1234');

if ($response->getStatusCode() !== 204) {
    throw new Exception('Failed to delete item 1234');
}
```

For making asynchronous requests use the `start*()` and `end()` methods:
```php
$handleOne = $apiClient->startGet('resourceName', '1234');
$handleTwo = $apiClient->startGet('resourceName', '5678');

$responseOne = $apiClient->end($handleOne);
$responseTwo = $apiClient->end($handleTwo);

if ($responseOne->getStatusCode() !== 200) {
    throw new Exception('Failed to fetch item 1234');
}

if ($responseTwo->getStatusCode() !== 200) {
    throw new Exception('Failed to fetch item 5678');
}

$itemOne = json_decode($responseOne->getBody(), true);
$itemTwo = json_decode($responseTwo->getBody(), true);

echo "Fetched item {$itemOne['foo']}\n";
echo "Fetched item {$itemTwo['foo']}\n";
```

### Cache
The library allows for a [PSR-16 SimpleCache](https://www.php-fig.org/psr/psr-16/) implementation.

### Collection

This is the preferred way to make index requests so that you don't have to handle (or forget to handle!) pagination yourself.  Using this iterator is simple with the API Client.  As an example, here is a snippet of code that will create a dropdown list of items.
**WARNING** Updates should not be performed to the items in the collection while interating as this may change the pagination.
```php
<ul>
<?php
$items = new \TraderInteractive\Api\Collection(
    $apiClient,
    'resourceName',
    array('aFilter' => '5')
);
foreach ($items as $item) {
    echo "<li>{$item['foo']}</li>\n";
}
?>
</ul>
```

## Contributing

If you would like to contribute, please use our build process for any changes and after the build passes, send us a pull request on github!

There is also a [docker](http://www.docker.com/)-based [fig](http://www.fig.sh/) configuration that will standup docker containers for the databases, execute the build inside a docker container, and then terminate everything.  This is an easy way to build the application:
```sh
fig run build
```
