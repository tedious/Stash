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
use Stash\Exception\RuntimeException;
use Stash\Interfaces\DriverInterface;

/**
 * Composite is a wrapper around one or more StashDrivers, allowing faster caching engines with size or
 * persistence limitations to be backed up by slower but larger and more persistent caches. There are no artificial
 * limits placed on how many drivers can be staggered.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Composite implements DriverInterface
{

    /**
     * The drivers this driver encapsulates.
     *
     * @var \Stash\Interfaces\DriverInterface[]
     */
    protected $drivers = array();

    /**
     * Takes an array of Drivers.
     *
     * {@inheritdoc}
     *
     * @throws \Stash\Exception\RuntimeException
     */
    public function setOptions(array $options = array())
    {
        if (!isset($options['drivers']) || !is_array($options['drivers']) || count($options['drivers']) < 1) {
            throw new RuntimeException('One or more secondary drivers are required.');
        }

        foreach ($options['drivers'] as $driver) {
            if (!(is_object($driver) && $driver instanceof DriverInterface)) {
                continue;
            }

            $this->drivers[] = $driver;
        }

        if (count($this->drivers) < 1) {
            throw new RuntimeException('None of the secondary drivers can be enabled.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __destruct()
    {
    }

    /**
     * This starts with the first driver and keeps trying subsequent drivers until a result is found. It then fills
     * in the result to any of the drivers that failed to retrieve it.
     *
     * {@inheritdoc}
     */
    public function getData($key)
    {
        $failedDrivers = array();
        $return = false;
        foreach ($this->drivers as $driver) {
            if ($return = $driver->getData($key)) {
                $failedDrivers = array_reverse($failedDrivers);
                foreach ($failedDrivers as $failedDriver) {
                    $failedDriver->storeData($key, $return['data'], $return['expiration']);
                }

                break;
            } else {
                $failedDrivers[] = $driver;
            }
        }

        return $return;
    }

    /**
     * This function stores the passed data on all drivers, starting with the most "distant" one (the last fallback) so
     * in order to prevent race conditions.
     *
     * {@inheritdoc}
     */
    public function storeData($key, $data, $expiration)
    {
        return $this->actOnAll('storeData', array($key, $data, $expiration));
    }

    /**
     * This function clears the passed key on all drivers, starting with the most "distant" one (the last fallback) so
     * in order to prevent race conditions.
     *
     * {@inheritdoc}
     */
    public function clear($key = null)
    {
        return $this->actOnAll('clear', array($key));
    }

    /**
     * This function runs the purge operation on all drivers.
     *
     * {@inheritdoc}
     */
    public function purge()
    {
        return $this->actOnAll('purge');
    }

    /**
     * This function runs the suggested action on all drivers in the reverse order, passing arguments when called for.
     *
     * @param  string $action purge|clear|storeData
     * @param  array  $args
     * @return bool
     */
    protected function actOnAll($action, $args = array())
    {
        $drivers = array_reverse($this->drivers);
        $return = true;
        $results = false;
        foreach ($drivers as $driver) {
            switch ($action) {
                case 'purge':
                    $results = $driver->purge();
                    break;
                case 'clear':
                    $results = $driver->clear($args[0]);
                    break;
                case 'storeData':
                    $results = $driver->storeData($args[0], $args[1], $args[2]);
                    break;
            }
            $return = $return && $results;
        }

        return $return;
    }

    /**
     * This function checks to see if this driver is available. This always returns true because this
     * driver has no dependencies, being a wrapper around other classes.
     *
     * {@inheritdoc}
     * @return bool true
     */
    public static function isAvailable()
    {
        return true;
    }
}
