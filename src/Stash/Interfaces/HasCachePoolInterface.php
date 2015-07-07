<?php

namespace Stash\Interfaces;

/**
 * Holds an cache pool.
 */
interface HasCachePoolInterface
{
    /**
     * Sets the cache pool for the object.
     *
     * @param \Stash\Interfaces\PoolInterface
     *
     * @return static
     */
    public function setCachePool(PoolInterface $cachePool);

    /**
     * Get the cache pool of the object.
     *
     * @return \Stash\Interfaces\PoolInterface
     */
    public function getCachePool();
}
