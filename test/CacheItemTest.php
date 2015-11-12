<?php
/**
 * @copyright: 2015 Matt Kynaston <matt@kynx.org>
 * @license: BSD-3-Clause
 */

namespace KynxTest\ZendCache\Psr;

use DateTime;
use Kynx\ZendCache\Psr\CacheItem;
use PHPUnit_Framework_TestCase as TestCase;

class CacheItemTest extends TestCase
{
    public function testConstructorIsHit()
    {
        $item = new CacheItem('key', 'value', true);
        $this->assertEquals('key', $item->getKey());
        $this->assertEquals('value', $item->get());
        $this->assertTrue($item->isHit());
    }

    public function testConstructorIsNotHit()
    {
        $item = new CacheItem('key', 'value', false);
        $this->assertEquals('key', $item->getKey());
        $this->assertNull($item->get());
        $this->assertFalse($item->isHit());
    }

    public function testSet()
    {
        $item = new CacheItem('key', 'value', true);
        $item->set('value2');
        $this->assertEquals('value2', $item->get());
    }

    public function testExpireAtDateTime()
    {
        $item = new CacheItem('key', 'value', true);
        $dateTime = new DateTime();
        $item->expiresAt($dateTime);
        $this->assertEquals($dateTime, $item->getExpiration());
    }

    public function testExpireAtNull()
    {
        $item = new CacheItem('key', 'value', true);
        $item->expiresAt(null);
        $this->assertNull($item->getExpiration());
    }

    public function testExpiresAfterInt()
    {
        $item = new CacheItem('key', 'value', true);
        $item->expiresAfter(3600);
        $expiration = $item->getExpiration();
        $this->assertNotNull($expiration);
        /* @var DateTime $expiration */
        $interval = $expiration->diff(new DateTime(), true);
        // better hope the test isn't running real slow...
        $this->assertEquals(1, $interval->h);
    }

    public function testExpiresAfterInterval()
    {
        $item = new CacheItem('key', 'value', true);
        $interval = new \DateInterval('PT1H');
        $item->expiresAfter($interval);
        $expiration = $item->getExpiration();
        $this->assertNotNull($expiration);
        /* @var DateTime $expiration */
        $interval = $expiration->diff(new DateTime(), true);
        // check range in case test is running slowly...
        $this->assertEquals(1, $interval->h);
    }
}