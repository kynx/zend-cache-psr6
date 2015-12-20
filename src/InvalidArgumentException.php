<?php
/**
 */

namespace Kynx\ZendCache\Psr;

use Psr\Cache\InvalidArgumentException as InvalidArgumentExceptionInterface;

class InvalidArgumentException extends \InvalidArgumentException implements InvalidArgumentExceptionInterface
{
}
