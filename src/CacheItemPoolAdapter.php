<?php
/**
 * @copyright: 2015 Matt Kynaston <matt@kynx.org>
 * @license: BSD-3-Clause
 */

namespace Kynx\ZendCache\Psr;

use Kynx\ZendCache\Psr\Spec\CacheItemInterface;
use Kynx\ZendCache\Psr\Spec\CacheItemPoolInterface;
use Zend\Cache\Exception;
use Zend\Cache\Storage\ClearByNamespaceInterface;
use Zend\Cache\Storage\FlushableInterface;
use Zend\Cache\Storage\StorageInterface;
use DateTime;

class CacheItemPoolAdapter implements CacheItemPoolInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Returns a Cache Item representing the specified key.
     *
     * This method must always return a CacheItemInterface object, even in case of
     * a cache miss. It MUST NOT return null.
     *
     * @param string $key
     *   The key for which to return the corresponding Cache Item.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return CacheItemInterface
     *   The corresponding Cache Item.
     */
    public function getItem($key)
    {
        $this->validateKey($key);
        try {
            $cacheItem = $this->storage->getItem($key, $success);

        } catch (Exception\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);

        } catch (Exception\ExceptionInterface $e) {
            throw new CacheException($e->getMessage(), $e->getCode(), $e);
        }

        return new CacheItem($key, $success ? $cacheItem : null, $success);
    }

    /**
     * Returns a traversable set of cache items.
     *
     * @param array $keys
     * An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return array|\Traversable
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function getItems(array $keys = [])
    {
        $this->validateKeys($keys);
        try {
            $cacheItems = $this->storage->getItems($keys);

        } catch (Exception\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);

        } catch (Exception\ExceptionInterface $e) {
            throw new CacheException($e->getMessage(), $e->getCode(), $e);
        }

        $items = [];
        foreach ($cacheItems as $key => $value) {
            $items[$key] = new CacheItem($key, $value, true);
        }

        // Return empty items for any keys that where not found
        foreach (array_diff($keys, array_keys($cacheItems)) as $key) {
            $items[$key] = new CacheItem($key, null, false);
        }

        return $items;
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * Note: This method MAY avoid retrieving the cached value for performance reasons.
     * This could result in a race condition with CacheItemInterface::get(). To avoid
     * such situation use CacheItemInterface::isHit() instead.
     *
     * @param string $key
     *    The key for which to check existence.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *  True if item exists in the cache, false otherwise.
     */
    public function hasItem($key)
    {
        $this->validateKey($key);

        try {
            $hasItem = $this->storage->hasItem($key);

        } catch (Exception\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);

        } catch (Exception\ExceptionInterface $e) {
            throw new CacheException($e->getMessage(), $e->getCode(), $e);
        }

        return $hasItem;
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear()
    {
        $cleared = false;
        try {
            /* @todo Not at all sure about this... do we really want to flush()? What happens if the storage supports
            /*       namespaces, but none has been provided? */
            $options = $this->storage->getOptions();
            $namespace = $options->getNamespace();
            if ($this->storage instanceof ClearByNamespaceInterface && $namespace) {
                $cleared = $this->storage->clearByNamespace($namespace);
            } elseif ($this->storage instanceof FlushableInterface) {
                $cleared = $this->storage->flush();
            } else {
                throw new CacheException(sprintf("Storage %s does not support clear()", get_class($this->storage)));
            }
        } catch (Exception\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);

        } catch (Exception\ExceptionInterface $e) {
            throw new CacheException($e->getMessage(), $e->getCode(), $e);
        }

        return $cleared;
    }

    /**
     * Removes the item from the pool.
     *
     * @param string $key
     *   The key for which to delete
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully removed. False if there was an error.
     */
    public function deleteItem($key)
    {
        $this->validateKey($key);

        try {
            $deleted = $this->storage->removeItem($key);

        } catch (Exception\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);

        } catch (Exception\ExceptionInterface $e) {
            throw new CacheException($e->getMessage(), $e->getCode(), $e);
        }

        return $deleted;
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param array $keys
     *   An array of keys that should be removed from the pool.

     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     */
    public function deleteItems(array $keys)
    {
        $this->validateKeys($keys);

        try {
            $notDeleted = $this->storage->removeItems($keys);

        } catch (Exception\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);

        } catch (Exception\ExceptionInterface $e) {
            throw new CacheException($e->getMessage(), $e->getCode(), $e);
        }

        // @todo Is it an "error" if one of the keys does not exist??
        return empty($notDeleted);
    }

    /**
     * Persists a cache item immediately.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   True if the item was successfully persisted. False if there was an error.
     */
    public function save(CacheItemInterface $item)
    {
        if (! $item instanceof CacheItem) {
            throw new InvalidArgumentException('$item must be an instance of ' . CacheItem::class);
        }

        $this->validateKey($item->getKey());

        try {
            $options = false;
            $expiration = $item->getExpiration();

            // @todo I can't see any way to set the TTL on an individual item except by temporarily overwriting the
            //       option on the storage adapter. Not sure if all storage adapters will support this...
            if ($expiration instanceof DateTime) {
                $options = $this->storage->getOptions();
                $new = clone $options;
                $interval = $expiration->diff(new DateTime(), true);
                $new->setTtl($interval->format('%s'));
                $this->storage->setOptions($new);
            }

            $saved = $this->storage->setItem($item->getKey(), $item->get());

            if ($options) {
                $this->storage->setOptions($options);
            }

        } catch (Exception\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);

        } catch (Exception\ExceptionInterface $e) {
            throw new CacheException($e->getMessage(), $e->getCode(), $e);
        }

        return $saved;
    }

    /**
     * Sets a cache item to be persisted later.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   False if the item could not be queued or if a commit was attempted and failed. True otherwise.
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        // can't see that any adapters support this, and spec allows us to save immediately...
        return $this->save($item);
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     *   True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     */
    public function commit()
    {
        return true;
    }

    private function validateKey($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException(sprintf("Key must be a string, '%s' given", gettype($key)));
        }
    }

    private function validateKeys($keys)
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
        }
    }
}
