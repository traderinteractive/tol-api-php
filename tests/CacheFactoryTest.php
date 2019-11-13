<?php

namespace TraderInteractive\Api;

use MongoDB;
use PHPUnit\Framework\TestCase;
use SubjectivePHP\Psr\SimpleCache\InMemoryCache;
use SubjectivePHP\Psr\SimpleCache\MongoCache;
use SubjectivePHP\Psr\SimpleCache\NullCache;
use SubjectivePHP\Psr\SimpleCache\RedisCache;

/**
 * @coversDefaultClass \TraderInteractive\Api\CacheFactory
 * @covers ::<private>
 */
final class CacheFactoryTest extends TestCase
{
    /**
     * @param string $name   The name of the cache to create.
     * @param array  $config Config data to pass to the factory.
     *
     * @test
     * @covers ::make
     * @dataProvider provideMakeData
     */
    public function makeCache(string $name, array $config)
    {
        $this->assertInstanceOf($name, CacheFactory::make($name, $config));
    }

    /**
     * @return array
     */
    public function provideMakeData() : array
    {
        return [
            'null cache' => [
                'name' => NullCache::class,
                'config' => [],
            ],
            'in-memory cache' => [
                'name' => InMemoryCache::class,
                'config' => [],
            ],
            'mongo cache with collection' => [
                'name' => MongoCache::class,
                'config' => [
                    'collection' => $this->getMockBuilder(\MongoDB\Collection::class)
                        ->disableOriginalConstructor()
                        ->getMock(),
                ],
            ],
            'mongo cache' => [
                'name' => MongoCache::class,
                'config' => [
                    'uri' => 'mongodb://localhost:27017',
                    'database' => 'testing',
                    'collection' => 'cache',
                ],
            ],
        ];
    }

    /**
     * @test
     * @covers ::make
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot create cache instance of 'Invalid'
     */
    public function cannotMakeUnsupportedCacheInstance()
    {
        CacheFactory::make('Invalid', []);
    }
}
