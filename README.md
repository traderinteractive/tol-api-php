# PHP Client for [REST](http://en.wikipedia.org/wiki/Representational_state_transfer) APIs

[![Build Status](http://img.shields.io/travis/dominionenterprises/tol-api-php.svg?style=flat)](https://travis-ci.org/dominionenterprises/tol-api-php)
[![Latest Stable Version](http://img.shields.io/packagist/v/dominionenterprises/tol-api.svg?style=flat)](https://packagist.org/packages/dominionenterprises/tol-api)
[![Total Downloads](http://img.shields.io/packagist/dt/dominionenterprises/tol-api.svg?style=flat)](https://packagist.org/packages/dominionenterprises/tol-api)
[![License](http://img.shields.io/packagist/l/dominionenterprises/tol-api.svg?style=flat)](https://packagist.org/packages/dominionenterprises/tol-api)

This is a PHP client for [REST](http://en.wikipedia.org/wiki/Representational_state_transfer) APIs like the TraderOnline APIs.

## Requirements

See [composer specification](composer.json).

## Installation

tol-api-php can be installed for use in your project using [composer](http://getcomposer.org).

The recommended way of using this library in your project is to add a `composer.json` file to your project.  The following contents would add tol-api-php as a dependency:

```json
{
    "require": {
        "dominionenterprises/tol-api": "~0.1.0"
    }
}
```

## Basic Usage

The basic guzzle client, without caching or automated pagination handling is mostly easy to work with.

To instantiate a client, you need the guzzle adapter, client id, client secret, and API url.  This client should work with apis like the TOL APIs.
```php
$apiAdapter = new \DominionEnterprises\Api\GuzzleAdapter();
$auth = \DominionEnterprisees\Api\Authentication::createClientCredentials(
    'clientId',
    'clientSecret'
)
$apiClient = new \DominionEnterprises\Api\Client(
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

if ($response->getHttpCode() !== 200) {
    throw new Exception('Non successful index call');
}

$body = $response->getResponse();
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

if ($response->getHttpCode() !== 200) {
    throw new Exception('Failed to fetch item 1234');
}

$item = $response->getResponse();
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

if ($response->getHttpCode() !== 201) {
    throw new Exception('Failed to create item foo');
}

$item = $response->getResponse();
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

if ($response->getHttpCode() !== 200) {
    throw new Exception('Failed to update item 1234');
}
```

For deleting an item, you can use the `delete` method:
```php
// Delete item 1234.
$response = $apiClient->delete('resourceName', '1234');

if ($response->getHttpCode() !== 204) {
    throw new Exception('Failed to delete item 1234');
}
```

For making asynchronous requests use the start*() and end() methods:
```php
$handleOne = $apiClient->startGet('resourceName', '1234');
$handleTwo = $apiClient->startGet('resourceName', '5678');

$responseOne = $apiClient->end($handleOne);
$responseTwo = $apiClient->end($handleTwo);

if ($responseOne->getHttpCode() !== 200) {
    throw new Exception('Failed to fetch item 1234');
}

if ($responseTwo->getHttpCode() !== 200) {
    throw new Exception('Failed to fetch item 5678');
}

$itemOne = $responseOne->getResponse();
$itemTwo = $responseTwo->getResponse();

echo "Fetched item {$itemOne['foo']}\n";
echo "Fetched item {$itemTwo['foo']}\n";
```

### Cache

Here's an example of instantiating the included mongo cache adapter.

Note that it is important to call ensureIndexes() on the mongo cache adapter at least once before using the cache.  This can be done when building/deploying the application, manually before release, or on application startup.  Without calling this method, the index for expiry won't be set and the responses will never be removed from the collection effectively making all cached objects stay cached forever.  **Important**: Calling it every time you use the cache is not recommended because it can be a very expensive call that can cause extreme load on the mongo database.

```php
$cacheAdapter = new \DominionEnterprises\Api\MongoCache(
    $mongoUrl,
    $mongoDbName,
    $mongoCollectionName
);

// This has to be called at least once to set the expiry index.
// Without this call, the response won't ever expire. This should
// definitely not be done on every request.  It can cause extreme
// load on the mongo database and should therefore be done
// sparingly.
$cacheAdapter->ensureIndexes();
```

And then later, you can use the cache adapter when creating the client and all of the GET requests made using that client will be sent through the cache to check if they exist, and if not, any successful responses from the API that include Expires headers will be cached for future calls.

```php
$cacheAdapter = new \DominionEnterprises\Api\MongoCache(
    $mongoUrl,
    $mongoDbName,
    $mongoCollectionName
);

$apiClient = new \DominionEnterprises\Api\Client(
    $apiAdapter,
    $auth,
    $apiBaseUrl,
    $cacheAdapter
);

// Assuming this doesn't exist in the cache, it will fetch from the
// API and store the result in the cache.
$apiClient->index('resourceName');

// Because of the above call, the response should already be
// cached.  This will mean that no further requests are being made
// to the API.
$apiClient->index('resourceName');
```

### Collection

This is the preferred way to make index requests so that you don't have to handle (or forget to handle!) pagination yourself.  Using this iterator is simple with the API Client.  As an example, here is a snippet of code that will create a dropdown list of items.
```php
<ul>
<?php
$items = new \DominionEnterprises\Api\Collection(
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

If you would like to contribute, please use our build process for any changes and after the build passes, send us a pull request on github!  The build requires a running mongo and redis.  The URI's to these services can be specified via environment variables or left to their defaults (localhost on the default port):
```sh
TESTING_MONGO_URL=mongodb://127.0.0.1:27017 TESTING_REDIS_URL=tcp://127.0.0.7:6379 ./build.php
```

There is also a [docker](http://www.docker.com/)-based build script that will standup docker containers for the databases, execute the build inside a docker container, and then terminate everything.  This is an easy way to build the application:
```sh
./dockerBuild.php
```
