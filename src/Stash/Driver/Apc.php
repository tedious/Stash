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
 * The APC driver is a wrapper for the APC extension, which allows developers to store data in memory.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Apc implements DriverInterface
{
    /**
     * Default maximum time an Item will be stored.
     *
     * @var int
     */
    protected $ttl = 300;

    /**
     * This is an install specific namespace used to segment different applications from interacting with each other
     * when using APC. It's generated by creating an md5 of this file's location.
     *
     * @var string
     */
    protected $apcNamespace;

    /**
     * The number of records \ApcIterator will grab at once.
     *
     * @var int
     */
    protected $chunkSize = 100;

    /**
     * Initializes the driver.
     *
     * @throws RuntimeException 'Extension is not installed.'
     */
    public function __construct()
    {
        if (!static::isAvailable()) {
            throw new RuntimeException('Extension is not installed.');
        }
    }

    /**
     * This function should takes an array which is used to pass option values to the driver.
     *
     * * ttl - This is the maximum time the item will be stored.
     * * namespace - This should be used when multiple projects may use the same library.
     *
     * @param  array                             $options
     * @throws \Stash\Exception\RuntimeException
     */
    public function setOptions(array $options = array())
    {
        if (isset($options['ttl']) && is_numeric($options['ttl'])) {
            $this->ttl = (int) $options['ttl'];
        }

        $this->apcNamespace = isset($options['namespace']) ? $options['namespace'] : md5(__FILE__);
    }

    /**
     * {@inheritdoc}
     */
    public function __destruct()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getData($key)
    {
        $keyString = self::makeKey($key);
        $success = null;
        $data = apc_fetch($keyString, $success);

        return $success ? $data : false;
    }

    /**
     * {@inheritdoc}
     */
    public function storeData($key, $data, $expiration)
    {
        $life = $this->getCacheTime($expiration);

        return apc_store($this->makeKey($key), array('data' => $data, 'expiration' => $expiration), $life);
    }

    /**
     * {@inheritdoc}
     */
    public function clear($key = null)
    {
        if (!isset($key)) {
            return apc_clear_cache('user');
        } else {
            $keyRegex = '[' . $this->makeKey($key) . '*]';
            $chunkSize = isset($this->chunkSize) && is_numeric($this->chunkSize) ? $this->chunkSize : 100;

            do {
                $emptyIterator = true;
                $it = new \APCIterator('user', $keyRegex, \APC_ITER_KEY, $chunkSize);
                foreach ($it as $item) {
                    $emptyIterator = false;
                    apc_delete($item['key']);
                }
            } while (!$emptyIterator);

        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        $now = time();
        $keyRegex = '[' . $this->makeKey(array()) . '*]';
        $chunkSize = isset($this->chunkSize) && is_numeric($this->chunkSize) ? $this->chunkSize : 100;

        $it = new \APCIterator('user', $keyRegex, \APC_ITER_KEY, $chunkSize);
        foreach ($it as $item) {
            $success = null;
            $data = apc_fetch($item['key'], $success);

            if ($success && is_array($data) && $data['expiration'] <= $now) {
                apc_delete($item['key']);
            }
        }

        return true;
    }

    /**
     * This driver is available if the apc extension is present and loaded on the system.
     *
     * @return bool
     */
    public static function isAvailable()
    {
        // HHVM has some of the APC extension, but not all of it.
        if (!class_exists('\APCIterator')) {
            return false;
        }

        return (extension_loaded('apc') && ini_get('apc.enabled'))
            && ((php_sapi_name() !== 'cli') || ini_get('apc.enable_cli'));
    }

    /**
     * Turns a key array into a string.
     *
     * @param  array  $key
     * @return string
     */
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

    /**
     * Converts a timestamp into a TTL.
     *
     * @param  int $expiration
     * @return int
     */
    protected function getCacheTime($expiration)
    {
        $life = $expiration - time();

        return $this->ttl < $life ? $this->ttl : $life;
    }

}
