<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Driver;

use Stash;

/**
 * The ephemeral class exists to assist with testing the main Stash class. Since this is a very minimal driver we can
 * test Stash without having to worry about underlying problems interfering.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Ephemeral extends AbstractDriver
{
    /**
     * Contains the cached data.
     *
     * @var array
     */
    protected $store = array();

    /**
     * Maximum number of items to cache
     *
     * @var int
     */
    protected $maxItems = 0;

    /**
     * Limit by memory utilized by PHP
     *
     * @var int
     */
    protected $memoryLimit = 0;

    /**
     * How aggressive to perform eviction in case of memory limit violation.
     * Should be positive float less than 1.
     *
     * @var float
     */
    private $memoryLimitEvictionFactor;

    public function getDefaultOptions()
    {
        return [
            'maxItems' => 0,
            'memoryLimit' => 0,
            'memoryLimitEvictionFactor' => 0.25,
        ];
    }

    /**
     * Allows setting maxItems.
     *
     * @param array $options
     *                       If maxItems is 0, infinite items will be cached
     */
    protected function setOptions(array $options = array())
    {
        $options += $this->getDefaultOptions();

        $this->maxItems = self::positiveIntegerOption($options, 'maxItems');
        $this->memoryLimit = self::positiveIntegerOption($options, 'memoryLimit');
        $this->memoryLimitEvictionFactor = self::factorOption($options, 'memoryLimitEvictionFactor');

        if ($this->maxItems > 0 && count($this->store) > $this->maxItems) {
            $this->evict(count($this->store) - $this->maxItems);
        }
    }

    /**
     * Evicts the first $count items that were added to the store.
     *
     * Subclasses could implement more advanced eviction policies.
     *
     * @param int $count
     */
    protected function evict($count)
    {
        while (
          $count-- > 0
          && array_shift($this->store) !== null
        ) {
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getData($key)
    {
        $key = $this->getKeyIndex($key);

        return isset($this->store[$key]) ? $this->store[$key] : false;
    }

    /**
     * Converts the key array into a passed function
     *
     * @param  array  $key
     * @return string
     */
    protected function getKeyIndex($key)
    {
        $index = '';
        foreach ($key as $value) {
            $index .= str_replace('#', '#:', $value) . '#';
        }

        return $index;
    }

    /**
     * {@inheritdoc}
     */
    public function storeData($key, $data, $expiration)
    {
        if ($this->maxItems > 0 && count($this->store) >= $this->maxItems) {
            $this->evict((count($this->store) + 1) - $this->maxItems);
        }

        $this->store[$this->getKeyIndex($key)] = array('data' => $data, 'expiration' => $expiration);

        if ($this->memoryLimit > 0) {
            while (count($this->store) && memory_get_usage() > $this->memoryLimit) {
                $offset = max(
                    ceil(count($this->store) * $this->memoryLimitEvictionFactor),
                    1
                );

                $this->store = array_slice($this->store, $offset, null, true);
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($key = null)
    {
        if (!isset($key)) {
            $this->store = array();
        } else {
            $clearIndex = $this->getKeyIndex($key);
            foreach ($this->store as $index => $data) {
                if (strpos($index, $clearIndex) === 0) {
                    unset($this->store[$index]);
                }
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        $now = time();
        foreach ($this->store as $index => $data) {
            if ($data['expiration'] <= $now) {
                unset($this->store[$index]);
            }
        }

        return true;
    }

    /**
     * @param array $options
     * @param string $optionName
     * @return int
     * @throws Stash\Exception\InvalidArgumentException
     */
    protected static function positiveIntegerOption(array $options, $optionName)
    {
        $optionValue = $options[$optionName];

        if (!is_int($optionValue) || $optionValue < 0) {
            throw new Stash\Exception\InvalidArgumentException(
                $optionName . ' must be a positive integer.'
            );
        }

        return $optionValue;
    }

    /**
     * @param array $options
     * @param string $optionName
     * @return int
     * @throws Stash\Exception\InvalidArgumentException
     */
    protected static function factorOption(array $options, $optionName)
    {
        $optionValue = $options[$optionName];

        if (!is_float($optionValue) || $optionValue < 0 || $optionValue > 1) {
            throw new Stash\Exception\InvalidArgumentException(
                $optionName . ' must be a factor 0..1'
            );
        }

        return $optionValue;
    }
}
