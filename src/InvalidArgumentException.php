<?php
/**
 */

namespace Kynx\ZendCache\Psr;

use Kynx\ZendCache\Psr\Spec\InvalidArgumentException as InvalidArgumentExceptionInterface;

class InvalidArgumentException extends \InvalidArgumentException implements InvalidArgumentExceptionInterface
{
}