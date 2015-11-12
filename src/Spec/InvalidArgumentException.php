<?php
/**
 *
 */

namespace Kynx\ZendCache\Psr\Spec;

/**
 * This is a placeholder until the PSR-6 is accepted and published. It's here for code completion / reference only.
 *
 * Exception interface for invalid cache arguments.
 *
 * Any time an invalid argument is passed into a method it must throw an
 * exception class which implements Psr\Cache\InvalidArgumentException.
 * @link https://github.com/php-fig/fig-standards/blob/master/proposed/cache.md
 */
interface InvalidArgumentException extends CacheException
{
}