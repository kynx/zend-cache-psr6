<?php
/**
 *
 */

namespace Kynx\ZendCache\Psr;

use Kynx\ZendCache\Psr\Spec\CacheException as CacheExceptionInterface;

class CacheException extends \Exception implements CacheExceptionInterface
{
}