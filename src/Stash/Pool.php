<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash;

use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;
use Stash\Exception\InvalidArgumentException;
use Stash\Driver\Ephemeral;
use Stash\Interfaces\DriverInterface;
use Stash\Interfaces\ItemInterface;
use Stash\Interfaces\PoolInterface;

/**
 *
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Pool implements PoolInterface
{
    /**
     * The cacheDriver being used by the system. While this class handles all of the higher functions, it's the cache
     * driver here that handles all of the storage/retrieval functionality. This value is set by the constructor.
     *
     * @var \Stash\Interfaces\DriverInterface
     */
    protected $driver;

    /**
     * Is this Pool disabled.
     *
     * @var bool
     */
    protected $isDisabled = false;

    /**
     * Default "Item" class to use for making new items.
     *
     * @var string
     */
    protected $itemClass = '\Stash\Item';

    /**
     * If set various then errors and exceptions will get passed to the PSR Compliant logging library. This
     * can be set using the setLogger() function in this class.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Current namespace, if any.
     *
     * @var string
     */
    protected $namespace;

    /**
     * The default cache invalidation method for items created by this pool object.
     *
     * @var int
     */
    protected $invalidationMethod = Invalidation::PRECOMPUTE;

    /**
     * Argument 1 for the default cache invalidation method
     *
     * @var mixed
     */
    protected $invalidationArg1 = null;

    /**
     * Argument 2 for the default cache invalidation method
     *
     * @var mixed
     */
    protected $invalidationArg2 = null;

    /**
     * The constructor takes a Driver class which is used for persistent
     * storage. If no driver is provided then the Ephemeral driver is used by
     * default.
     *
     * @param DriverInterface $driver
     */
    public function __construct(?DriverInterface $driver = null)
    {
        if (isset($driver)) {
            $this->setDriver($driver);
        } else {
            $this->driver = new Ephemeral();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setItemClass(string $class): bool
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException('Item class ' . $class . ' does not exist');
        }

        $interfaces = class_implements($class, true);

        if (!in_array('Stash\Interfaces\ItemInterface', $interfaces)) {
            throw new \InvalidArgumentException('Item class ' . $class . ' must inherit from \Stash\Interfaces\ItemInterface');
        }

        $this->itemClass = $class;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): CacheItemInterface
    {
        $keyString = trim($key, '/');
        $key = explode('/', $keyString);
        $namespace = empty($this->namespace) ? 'stash_default' : $this->namespace;

        array_unshift($key, $namespace);

        foreach ($key as $node) {
            if (!isset($node[1]) && strlen($node) < 1) {
                throw new InvalidArgumentException('Invalid or Empty Node passed to getItem constructor.');
            }
        }

        /** @var ItemInterface $item */
        $item = new $this->itemClass();
        $item->setPool($this);
        $item->setKey($key, $namespace);
        $item->setInvalidationMethod($this->invalidationMethod, $this->invalidationArg1, $this->invalidationArg2);

        if ($this->isDisabled) {
            $item->disable();
        }

        if (isset($this->logger)) {
            $item->setLogger($this->logger);
        }

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = array()): iterable
    {
        // temporarily cheating here by wrapping around single calls.

        $items = array();
        foreach ($keys as $key) {
            $item = $this->getItem($key);
            $items[$item->getKey()] = $item;
        }

        return new \ArrayIterator($items);
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem(string $key): bool
    {
        return $this->getItem($key)->isHit();
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        return $item->save();
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->save($item);
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        return true;
    }


    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        // temporarily cheating here by wrapping around single calls.
        $results = true;
        foreach ($keys as $key) {
            $results = $this->deleteItem($key) && $results;
        }

        return $results;
    }


    /**
     * {@inheritdoc}
     */
    public function deleteItem(string $key): bool
    {
        return $this->getItem($key)->clear();
    }


    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        if ($this->isDisabled) {
            return false;
        }

        try {
            $driver = $this->getDriver();
            if (isset($this->namespace)) {
                $normalizedNamespace = strtolower($this->namespace);
                $results = $driver->clear(array('cache', $normalizedNamespace))
                        && $driver->clear(array('sp', $normalizedNamespace));
            } else {
                $results = $driver->clear();
            }
        } catch (\Exception $e) {
            $this->isDisabled = true;
            $this->logException('Flushing Cache Pool caused exception.', $e);

            return false;
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function purge(): bool
    {
        if ($this->isDisabled) {
            return false;
        }

        try {
            $results = $this->getDriver()->purge();
        } catch (\Exception $e) {
            $this->isDisabled = true;
            $this->logException('Purging Cache Pool caused exception.', $e);

            return false;
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function setDriver(DriverInterface $driver): void
    {
        $this->driver = $driver;
    }

    /**
     * {@inheritdoc}
     */
    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * {@inheritdoc}
     */
    public function setNamespace(?string $namespace = null): bool
    {
        if (is_null($namespace)) {
            $this->namespace = null;

            return true;
        }

        if (!ctype_alnum($namespace)) {
            throw new \InvalidArgumentException('Namespace must be alphanumeric.');
        }

        $this->namespace = $namespace;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace(): bool|string
    {
        return isset($this->namespace) ? $this->namespace : false;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger): bool
    {
        $this->logger = $logger;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setInvalidationMethod(
        int $invalidation = Invalidation::PRECOMPUTE,
        mixed $arg = null,
        mixed $arg2 = null
    ): bool {
        $this->invalidationMethod = $invalidation;
        $this->invalidationArg1 = $arg;
        $this->invalidationArg2 = $arg2;

        return true;
    }

    /**
     * Logs an exception with the Logger class, if it exists.
     *
     * @param  string     $message
     * @param  \Exception $exception
     * @return bool
     */
    protected function logException(string $message, \Exception $exception): bool
    {
        if (!isset($this->logger)) {
            return false;
        }

        $this->logger->critical(
            $message,
            array('exception' => $exception)
        );

        return true;
    }
}
