<?php

namespace KynxTest\ZendCache\Psr;

use Cache\IntegrationTests\CachePoolTest;
use Kynx\ZendCache\Psr\CacheItemPoolAdapter;
use Zend\Cache\Storage\Adapter\Filesystem;

class IntegrationPoolTest extends CachePoolTest
{
    public function createCachePool()
    {
        $storage = new Filesystem(['cacheDir' => __DIR__.'/cache']);

        return new CacheItemPoolAdapter($storage);
    }
}
