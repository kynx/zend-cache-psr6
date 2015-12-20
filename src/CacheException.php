<?php
/**
 *
 */

namespace Kynx\ZendCache\Psr;

use Psr\Cache\CacheException as CacheExceptionInterface;

class CacheException extends \Exception implements CacheExceptionInterface
{
}
