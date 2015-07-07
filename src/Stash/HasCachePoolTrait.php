<?php

namespace Stash;

use Stash\Interfaces\PoolInterface;
use Psr\Log\LoggerInterface;

/**
 * Basic Implementation of PoolInterface.
 */
trait HasCachePoolTrait
{

    /**
     * @var \Stash\Interfaces\PoolInterface
     */
    protected $cachePool;

    /**
     * Sets the cache pool.
     *
     * @param \Stash\Interfaces\PoolInterface $cachePool
     *
     * @return static
     */
    public function setCachePool(PoolInterface $cachePool)
    {
        $this->cachePool = $cachePool;
        return $this;
    }

    /**
     * Returns the current cache pool.
     *
     * @return \Stash\Interfaces\PoolInterface
     */
    public function getCachePool()
    {
        if (!isset($this->cachePool)) {
            $this->cachePool = new Pool();
            if (isset($this->logger) && $this->logger instanceof LoggerInterface) {
                $this->cachePool->setLogger($this->logger);
            }
        }
        return $this->cachePool;
    }
}
