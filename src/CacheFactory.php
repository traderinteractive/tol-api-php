<?php

namespace TraderInteractive\Api;

use MongoDB;
use Psr\SimpleCache\CacheInterface;
use SubjectivePHP\Psr\SimpleCache\InMemoryCache;
use SubjectivePHP\Psr\SimpleCache\MongoCache;
use SubjectivePHP\Psr\SimpleCache\NullCache;

final class CacheFactory
{
    /**
     * @param string $name   The name of the cache object to make.
     * @param array  $config Options to use when constructing the cache.
     *
     * @return CacheInterface
     */
    public static function make(string $name, array $config) : CacheInterface
    {
        if ($name === MongoCache::class) {
            return self::getMongoCache($config);
        }

        if ($name === InMemoryCache::class) {
            return new InMemoryCache();
        }

        if ($name === NullCache::class) {
            return new NullCache();
        }

        throw new \RuntimeException("Cannot create cache instance of '{$name}'");
    }

    private static function getMongoCache(array $config) : MongoCache
    {
        return new MongoCache(self::getMongoCollectionFromConfig($config), new ResponseSerializer());
    }

    private static function getMongoCollectionFromConfig(array $config) : MongoDB\Collection
    {
        $collection = $config['collection'];
        if ($collection instanceof MongoDB\Collection) {
            return $collection;
        }

        $uri = $config['uri'];
        $database = $config['database'];
        return (new MongoDB\Client($uri))->selectDatabase($database)->selectCollection($collection);
    }
}
