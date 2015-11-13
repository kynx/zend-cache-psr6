<?php
/**
 * @copyright: 2015 Matt Kynaston <matt@kynx.org>
 * @license: BSD-3-Clause
 */

namespace KynxTest\ZendCache\Psr;

use Kynx\ZendCache\Psr\CacheItem;
use Kynx\ZendCache\Psr\CacheItemPoolAdapter;
use Kynx\ZendCache\Psr\Spec\CacheItemInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Zend\Cache\Exception;
use Zend\Cache\Storage\Adapter\AdapterOptions;
use Zend\Cache\Storage\Adapter\Filesystem;
use Zend\Cache\Storage\Adapter\Memcache;
use Zend\Cache\Storage\StorageInterface;

class CacheItemPoolAdapterTest extends TestCase
{
    /**
     * @var Filesystem
     */
    protected $storage;
    /**
     * @var CacheItemPoolAdapter
     */
    protected $adapter;

    public function setUp()
    {
        $this->storage = new Filesystem(['cacheDir' => __DIR__ . '/cache']);
        $this->adapter = new CacheItemPoolAdapter($this->storage);
    }

    public function tearDown()
    {
        $this->storage->flush();
    }

    public function testGetNonexistentItem()
    {
        $item = $this->adapter->getItem('foo');
        $this->assertInstanceOf(CacheItem::class, $item);
        $this->assertEquals('foo', $item->getKey());
        $this->assertNull($item->get());
        $this->assertFalse($item->isHit());
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\InvalidArgumentException
     */
    public function testGetItemInvalidKey()
    {
        $this->adapter->getItem([]);
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\CacheException
     */
    public function testGetItemCacheException()
    {
        $prophesy = $this->getStorageProphesy();
        $prophesy->getItem(Argument::any(), Argument::any())->will(function () {
            throw new Exception\RuntimeException("thrown");
        });
        $adapter = new CacheItemPoolAdapter($prophesy->reveal());
        $adapter->getItem('foo');
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\InvalidArgumentException
     */
    public function testGetItemInvalidArgumentException()
    {
        $prophesy = $this->getStorageProphesy();
        $prophesy->getItem(Argument::any(), Argument::any())->will(function () {
            throw new Exception\InvalidArgumentException("thrown");
        });
        $adapter = new CacheItemPoolAdapter($prophesy->reveal());
        $adapter->getItem('foo');
    }

    public function testGetNonexistentItems()
    {
        $items = $this->adapter->getItems(['foo', 'bar']);
        $this->assertEquals(2, count($items));
        $this->assertEquals('foo', $items['foo']->getKey());
        $this->assertEquals('bar', $items['bar']->getKey());
        foreach ($items as $item) {
            $this->assertNull($item->get());
            $this->assertFalse($item->isHit());
        }
    }

    public function testGetMixedItems()
    {
        $item = $this->adapter->getItem('bar');
        $item->set('value');
        $this->adapter->save($item);

        $items = $this->adapter->getItems(['foo', 'bar']);
        $this->assertEquals(2, count($items));
        $this->assertEquals('foo', $items['foo']->getKey());
        $this->assertEquals('bar', $items['bar']->getKey());
        $this->assertNull($items['foo']->get());
        $this->assertFalse($items['foo']->isHit());
        $this->assertEquals('value', $items['bar']->get());
        $this->assertTrue($items['bar']->isHit());
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\InvalidArgumentException
     */
    public function testGetItemsInvalidKey()
    {
        $this->adapter->getItems(['foo', []]);
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\CacheException
     */
    public function testGetItemsCacheException()
    {
        $prophesy = $this->getStorageProphesy();
        $prophesy->getItems(Argument::any())->will(function () {
            throw new Exception\RuntimeException("thrown");
        });
        $adapter = new CacheItemPoolAdapter($prophesy->reveal());
        $adapter->getItems(['foo', 'bar']);
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\InvalidArgumentException
     */
    public function testGetItemsInvalidArgumentException()
    {
        $prophesy = $this->getStorageProphesy();
        $prophesy->getItems(Argument::any())->will(function () {
            throw new Exception\InvalidArgumentException("thrown");
        });
        $adapter = new CacheItemPoolAdapter($prophesy->reveal());
        $adapter->getItems(['foo', 'bar']);
    }

    public function testSaveItem()
    {
        $item = $this->adapter->getItem('foo');
        $item->set('bar');
        $this->adapter->save($item);
        $saved = $this->adapter->getItem('foo');
        $this->assertEquals('bar', $saved->get());
        $this->assertTrue($saved->isHit());
    }

    public function testSaveItemWithExpiration()
    {
        $item = $this->adapter->getItem('foo');
        $item->set('bar');
        $item->expiresAfter(3600);
        $this->adapter->save($item);
        $saved = $this->adapter->getItem('foo');
        $this->assertEquals('bar', $saved->get());
        $this->assertTrue($saved->isHit());
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\InvalidArgumentException
     */
    public function testSaveForeignItem()
    {
        $prophesy = $this->prophesize(CacheItemInterface::class);
        $this->adapter->save($prophesy->reveal());
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\CacheException
     */
    public function testSaveItemCacheException()
    {
        $prophesy = $this->getStorageProphesy();
        $prophesy->setItem(Argument::any(), Argument::any())->will(function () {
            throw new Exception\RuntimeException("thrown");
        });
        $prophesy->getItem(Argument::any(), Argument::any())->will(function () {
            return new CacheItem('foo', null, false);
        });
        $adapter = new CacheItemPoolAdapter($prophesy->reveal());
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $adapter->save($item);
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\InvalidArgumentException
     */
    public function testSaveItemInvalidArgumentException()
    {
        $prophesy = $this->getStorageProphesy();
        $prophesy->setItem(Argument::any(), Argument::any())->will(function () {
            throw new Exception\InvalidArgumentException("thrown");
        });
        $prophesy->getItem(Argument::any(), Argument::any())->will(function () {
            return new CacheItem('foo', null, false);
        });
        $adapter = new CacheItemPoolAdapter($prophesy->reveal());
        $item = $adapter->getItem('foo');
        $item->set('bar');
        $adapter->save($item);
    }

    public function testHasItem()
    {
        $item = $this->adapter->getItem('foo');
        $item->set('bar');
        $this->adapter->save($item);
        $this->assertTrue($this->adapter->hasItem('foo'));
    }

    public function testHasNonexistentItem()
    {
        $this->assertFalse($this->adapter->hasItem('foo'));
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\InvalidArgumentException
     */
    public function testHasItemInvalidKey()
    {
        $this->adapter->hasItem([]);
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\CacheException
     */
    public function testHasItemCacheException()
    {
        $prophesy = $this->getStorageProphesy();
        $prophesy->hasItem(Argument::any(), Argument::any())->will(function () {
            throw new Exception\RuntimeException("thrown");
        });
        $adapter = new CacheItemPoolAdapter($prophesy->reveal());
        $adapter->hasItem('foo');
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\InvalidArgumentException
     */
    public function testHasItemInvalidArgumentException()
    {
        $prophesy = $this->getStorageProphesy();
        $prophesy->hasItem(Argument::any(), Argument::any())->will(function () {
            throw new Exception\InvalidArgumentException("thrown");
        });
        $adapter = new CacheItemPoolAdapter($prophesy->reveal());
        $adapter->hasItem('foo');
    }

    public function testClear()
    {
        $item = $this->adapter->getItem('foo');
        $item->set('bar');
        $this->adapter->save($item);

        $this->assertTrue($this->adapter->clear());
    }


    public function testClearEmpty()
    {
        $this->assertTrue($this->adapter->clear());
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\CacheException
     */
    public function testClearCacheByNamespaceException()
    {
        $prophesy = $this->prophesize(Filesystem::class);
        $options = $this->prophesize(AdapterOptions::class);
        $options->getNamespace()->willReturn('foo');
        $prophesy->getOptions()->willReturn($options->reveal());
        $prophesy->clearByNamespace(Argument::any())->will(function () {
            throw new Exception\RuntimeException("thrown");
        });
        $adapter = new CacheItemPoolAdapter($prophesy->reveal());
        $adapter->clear();
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\InvalidArgumentException
     */
    public function testClearCacheByNamespaceInvalidArgumentException()
    {
        $prophesy = $this->prophesize(Filesystem::class);
        $options = $this->prophesize(AdapterOptions::class);
        $options->getNamespace()->willReturn('foo');
        $prophesy->getOptions()->willReturn($options->reveal());
        $prophesy->clearByNamespace(Argument::any())->will(function () {
            throw new Exception\InvalidArgumentException("thrown");
        });
        $adapter = new CacheItemPoolAdapter($prophesy->reveal());
        $adapter->clear();
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\CacheException
     */
    public function testClearFlushException()
    {
        $prophesy = $this->prophesize(Memcache::class);
        $options = $this->prophesize(AdapterOptions::class);
        $options->getNamespace()->willReturn(false);
        $prophesy->getOptions()->willReturn($options->reveal());
        $prophesy->flush()->will(function () {
            throw new Exception\RuntimeException("thrown");
        });
        $adapter = new CacheItemPoolAdapter($prophesy->reveal());
        $adapter->clear();
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\InvalidArgumentException
     */
    public function testClearFlushInvalidArgumentException()
    {
        $prophesy = $this->prophesize(Memcache::class);
        $options = $this->prophesize(AdapterOptions::class);
        $options->getNamespace()->willReturn(false);
        $prophesy->getOptions()->willReturn($options->reveal());
        $prophesy->flush()->will(function () {
            throw new Exception\InvalidArgumentException("thrown");
        });
        $adapter = new CacheItemPoolAdapter($prophesy->reveal());
        $adapter->clear();
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\CacheException
     */
    public function testClearNotSupported()
    {
        $prophesy = $this->getStorageProphesy();
        $options = $this->prophesize(AdapterOptions::class);
        $options->getNamespace()->willReturn(false);
        $prophesy->getOptions()->willReturn($options->reveal());
        $adapter = new CacheItemPoolAdapter($prophesy->reveal());
        $adapter->clear();
    }

    /**
     * @todo It isn't clear from PSR-6 whether deleting non-existent keys should return false
     */
    public function testDeleteNonexistentItem()
    {
        $this->assertFalse($this->adapter->deleteItem('foo'));
    }

    public function testDeleteItem()
    {
        $items = $this->adapter->getItems(['foo', 'bar']);
        $items['foo']->set('value1');
        $items['bar']->set('value2');
        $this->adapter->save($items['foo']);
        $this->adapter->save($items['bar']);

        $this->assertTrue($this->adapter->deleteItem('bar'));

        $items = $this->adapter->getItems(['foo', 'bar']);
        $this->assertEquals('value1', $items['foo']->get());
        $this->assertTrue($items['foo']->isHit());
        $this->assertNull($items['bar']->get());
        $this->assertFalse($items['bar']->isHit());
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\InvalidArgumentException
     */
    public function testDeleteItemInvalidKey()
    {
        $this->adapter->deleteItem([]);
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\CacheException
     */
    public function testDeleteItemCacheException()
    {
        $prophesy = $this->getStorageProphesy();
        $prophesy->removeItem(Argument::any())->will(function () {
            throw new Exception\RuntimeException("thrown");
        });
        $adapter = new CacheItemPoolAdapter($prophesy->reveal());
        $adapter->deleteItem('foo');
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\InvalidArgumentException
     */
    public function testDeleteItemInvalidArgumentException()
    {
        $prophesy = $this->getStorageProphesy();
        $prophesy->removeItem(Argument::any())->will(function () {
            throw new Exception\InvalidArgumentException("thrown");
        });
        $adapter = new CacheItemPoolAdapter($prophesy->reveal());
        $adapter->deleteItem('foo');
    }

    /**
     * @todo It isn't clear from PSR-6 whether deleting non-existent keys should return false
     */
    public function testDeleteNonexistentItems()
    {
        $this->assertFalse($this->adapter->deleteItems(['foo', 'foo2', 'baz']));
    }

    /**
     * @todo It isn't clear from PSR-6 whether deleting non-existent keys should return false
     */
    public function testDeleteItems()
    {

        $items = $this->adapter->getItems(['foo', 'bar', 'baz']);
        $items['foo']->set('value1');
        $items['bar']->set('value2');
        $items['baz']->set('value3');
        $this->adapter->save($items['foo']);
        $this->adapter->save($items['bar']);
        $this->adapter->save($items['baz']);

        $this->assertFalse($this->adapter->deleteItems(['foo', 'foo2', 'baz']));

        $items = $this->adapter->getItems(['foo', 'bar', 'baz']);
        $this->assertFalse($items['foo']->isHit());
        $this->assertTrue($items['bar']->isHit());
        $this->assertFalse($items['baz']->isHit());
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\InvalidArgumentException
     */
    public function testDeleteItemsInvalidKey()
    {
        $this->adapter->deleteItems(['foo', []]);
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\CacheException
     */
    public function testDeleteItemsCacheException()
    {
        $prophesy = $this->getStorageProphesy();
        $prophesy->removeItems(Argument::any())->will(function () {
            throw new Exception\RuntimeException("thrown");
        });
        $adapter = new CacheItemPoolAdapter($prophesy->reveal());
        $adapter->deleteItems(['foo', 'foo2', 'baz']);
    }

    /**
     * @expectedException \Kynx\ZendCache\Psr\InvalidArgumentException
     */
    public function testDeleteItemsInvalidArgumentException()
    {
        $prophesy = $this->getStorageProphesy();
        $prophesy->removeItems(Argument::any())->will(function () {
            throw new Exception\InvalidArgumentException("thrown");
        });
        $adapter = new CacheItemPoolAdapter($prophesy->reveal());
        $adapter->deleteItems(['foo', 'foo2', 'baz']);
    }

    public function testSaveDeferred()
    {
        $item = $this->adapter->getItem('foo');
        $item->set('bar');
        $this->adapter->saveDeferred($item);
        $saved = $this->adapter->getItem('foo');
        $this->assertEquals('bar', $saved->get());
        $this->assertTrue($saved->isHit());
    }

    public function testCommit()
    {
        $this->assertTrue($this->adapter->commit());
    }

    private function getStorageProphesy()
    {
        return $this->prophesize(StorageInterface::class);
    }
}
