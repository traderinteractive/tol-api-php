<?php

namespace TraderInteractive\Api;

use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \TraderInteractive\Api\CacheHelper
 */
final class CacheHelperTest extends TestCase
{
    /**
     * @test
     * @covers ::getCacheKey
     *
     * @return void
     */
    public function getCacheKey()
    {
        $request = new Psr7\Request('GET', 'http://localhost:8080/id', []);
        $this->assertSame(
            'GET|http_COLON__FSLASH__FSLASH_localhost_COLON_8080_FSLASH_id|',
            CacheHelper::getCacheKey($request)
        );
    }
}
