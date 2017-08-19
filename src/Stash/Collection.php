<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Lukas Klinzing <theluk@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash;

use Stash\Interfaces\ItemInterface;
use Stash\Interfaces\PoolInterface;
use Stash\Interfaces\CollectionInterface;
use ArrayIterator;

class Collection implements CollectionInterface
{

    /** @var mixed key/item map */
    protected $items;
    /** @var PoolInterface */
    protected $pool;
    /** @var mixed the fetched data for each key */
    protected $results = null;

    /**
     * Constructs a collection holding results of a multi get request
     * @param array        $keys list of key array
     * @param PoolInterface $pool
     */
    public function __construct($keys, PoolInterface $pool)
    {
        $this->pool = $pool;
        $this->items = array_reduce($keys, function ($map, $key) {
            $item = $this->pool->getItem($key);
            $item->setResultCollection($this);
            $map[$key] = $item;

            return $map;
        }, []);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $this->fetch();
        return new ArrayIterator($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function getRecord(ItemInterface $item)
    {
        $this->fetch();
        $key = serialize($item->getCacheKey());
        if (isset($this->results[$key])) {
            return $this->results[$key];
        }

        return [];
    }

    /**
     * fetches data for each key, if not already fetched
     * @return void
     */
    protected function fetch()
    {
        if (null === $this->results) {
            $this->results = [];

            // stringified versions of the array keys
            $ids = array_map(function ($item) {
                return $item->getCacheKey();
            }, $this->items);

            $driver = $this->pool->getDriver();
            $data = $driver->getMany($ids);
            if (empty($data)) {
                return;
            }

            foreach ($data as $item) {
                $this->results[serialize($item["key"])] = $item;
            }
        }
    }
}
