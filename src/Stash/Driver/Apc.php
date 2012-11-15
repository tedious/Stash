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

/**
 * The StashApc is a wrapper for the APC extension, which allows developers to store data in memory.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Apc implements DriverInterface
{
    protected $ttl = 300;
    protected $apcNamespace;

    /**
     * This function should takes an array which is used to pass option values to the driver.
     *
     * * ttl - This is the maximum time the item will be stored.
     * * namespace - This should be used when multiple projects may use the same library.
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        if (isset($options['ttl']) && is_numeric($options['ttl'])) {
            $this->ttl = (int)$options['ttl'];
        }

        $this->apcNamespace = isset($options['namespace']) ? $options['namespace'] : md5(__FILE__);

        if(!static::isAvailable()) {
            throw new RuntimeException('Extension is not installed.');
        }
    }

    /**
     * Empty destructor to maintain a standardized interface across all drivers.
     *
     */
    public function __destruct()
    {

    }

    /**
     * This function should return the data array, exactly as it was received by the storeData function, or false if it
     * is not present. This array should have a value for "createdOn" and for "return", which should be the data the
     * main script is trying to store.
     *
     * @param array $key
     * @return array
     */
    public function getData($key)
    {
        $keyString = self::makeKey($key);

        $data = apc_fetch($keyString, $success);

        return $success ? $data : false;
    }

    /**
     * This function takes an array as its first argument and the expiration time as the second. This array contains two
     * items, "createdOn" describing the first time the item was called and "return", which is the data that needs to be
     * stored. This function needs to store that data in such a way that it can be retrieved exactly as it was sent. The
     * expiration time needs to be stored with this data.
     *
     * @param array $key
     * @param array $data
     * @param int $expiration
     * @return bool
     */
    public function storeData($key, $data, $expiration)
    {
        $life = $this->getCacheTime($expiration);
        return apc_store($this->makeKey($key), array('data' => $data, 'expiration' => $expiration), $life);
    }

    /**
     * This function should clear the cache tree using the key array provided. If called with no arguments the entire
     * cache needs to be cleared.
     *
     * @param null|array $key
     * @return bool
     */
    public function clear($key = null)
    {
        if (!isset($key)) {
            return apc_clear_cache('user');
        } else {
            $keyRegex = '[' . $this->makeKey($key) . '*]';
            $chunkSize = isset($this->chunkSize) && is_numeric($this->chunkSize) ? $this->chunkSize : 100;
            $it = new \APCIterator('user', $keyRegex, \APC_ITER_KEY, $chunkSize);
            foreach ($it as $key) {
                apc_delete($key);
            }
        }
        return true;
    }

    /**
     * This function is used to remove expired items from the cache.
     *
     * @return bool
     */
    public function purge()
    {
        $now = time();
        $keyRegex = '[' . $this->makeKey(array()) . '*]';
        $chunkSize = isset($this->chunkSize) && is_numeric($this->chunkSize) ? $this->chunkSize : 100;
        $it = new \APCIterator('user', $keyRegex, \APC_ITER_KEY, $chunkSize);
        foreach ($it as $key) {
            $data = apc_fetch($key, $success);
            $data = $data[$key['key']];

            if ($success && is_array($data) && $data['expiration'] <= $now) {
                apc_delete($key);
            }
        }

        return true;
    }

    /**
     * This driver is available iff the apc extension is present and loaded on the system.
     *
     * @return bool
     */
    static public function isAvailable()
    {
        return extension_loaded('apc') && ini_get('apc.enabled');
    }

    protected function makeKey($key)
    {
        $keyString = md5(__FILE__) . '::'; // make it unique per install

        if (isset($this->apcNamespace)) {
            $keyString .= $this->apcNamespace . '::';
        }

        foreach ($key as $piece) {
            $keyString .= $piece . '::';
        }

        return $keyString;
    }

    protected function getCacheTime($expiration)
    {
        $life = $expiration - time(true);

        return $this->ttl > $life ? $this->ttl : $life;
    }

}
