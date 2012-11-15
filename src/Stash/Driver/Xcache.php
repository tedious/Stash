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
 * StashSqlite is a wrapper around the xcache php extension, which allows developers to store data in memory.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 *
 * @codeCoverageIgnore Just until I figure out how to get phpunit working over http, or xcache over cli
 */
class Xcache implements DriverInterface
{
    protected $ttl = 300;
    protected $user;
    protected $password;

    public function __construct(array $options = array())
    {
        if(!static::isAvailable()) {
            throw new RuntimeException('Extension is not installed.');
        }

        if (isset($options['user'])) {
            $this->user = $options['user'];
        }

        if (isset($options['password'])) {
            $this->password = $options['password'];
        }

        if (isset($options['ttl']) && is_numeric($options['ttl'])) {
            $this->ttl = (int)$options['ttl'];
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
        $keyString = $this->makeKey($key);
        if (!$keyString) {
            return false;
        }

        if (!xcache_isset($keyString)) {
            return false;
        }

        $data = xcache_get($keyString);
        return unserialize($data);
    }

    /**
     * This function takes an array as its first argument and the expiration time as the second. This array contains two
     * items, "createdOn" describing the first time the item was called and "return", which is the data that needs to be
     * stored. This function needs to store that data in such a way that it can be retrieved exactly as it was sent. The
     * expiration time needs to be stored with this data.
     *
     * @param array $data
     * @param int $expiration
     * @return bool
     */
    public function storeData($key, $data, $expiration)
    {
        $keyString = self::makeKey($key);
        if (!$keyString) {
            return false;
        }

        $cacheTime = $this->getCacheTime($expiration);
        return xcache_set($keyString, serialize(array('return' => $data, 'expiration' => $expiration)), $cacheTime);
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
        if (isset($key)) {
            $key = array();
        }

        $keyString = $this->makeKey($key);
        if (!$keyString) {
            return false;
        }

        return xcache_unset_by_prefix($keyString);
    }

    /**
     * This function is used to remove expired items from the cache.
     *
     * @return bool
     */
    public function purge()
    {
        return $this->clear();

        /*

                // xcache loses points for its login choice, but not as many as it gained for xcache_unset_by_prefix
                $original = array();
                if(isset($_SERVER['PHP_AUTH_USER']))
                {
                    $original['PHP_AUTH_USER'] = $_SERVER['PHP_AUTH_USER'];
                    unset($_SERVER['PHP_AUTH_USER']);
                }

                if(isset($_SERVER['PHP_AUTH_PW']))
                {
                    $original['PHP_AUTH_PW'] = $_SERVER['PHP_AUTH_PW'];
                    unset($_SERVER['PHP_AUTH_USER']);
                }

                if(isset($this->user))
                    $_SERVER['PHP_AUTH_USER'] = $this->user;

                if(isset($this->password))
                    $_SERVER['PHP_AUTH_PW'] = $this->password;

                if(isset($key) && function_exists('xcache_unset_by_prefix'))
                {
                    $keyString = self::makeKey($key);
                    if($keyString = self::makeKey($key))
                    {
                        // this is such a sexy function, soooo many points to xcache
                        $return = xcache_unset_by_prefix($keyString);
                    }else{
                        return false;
                    }
                }else{
                    xcache_clear_cache(XC_TYPE_VAR, 0);
                    $return = true;
                }

                if(isset($original['PHP_AUTH_USER']))
                {
                    $_SERVER['PHP_AUTH_USER'] = $original['PHP_AUTH_USER'];
                }elseif(isset($_SERVER['PHP_AUTH_USER'])){
                    unset($_SERVER['PHP_AUTH_USER']);
                }

                if(isset($original['PHP_AUTH_PW']))
                {
                    $_SERVER['PHP_AUTH_PW'] = $original['PHP_AUTH_PW'];
                }elseif(isset($_SERVER['PHP_AUTH_PW'])){
                    unset($_SERVER['PHP_AUTH_PW']);
                }

                return $return;



        */

    }

    /**
     * This function checks to see if it is possible to enable this driver.
     *
     * @return bool true
     */
    static public function isAvailable()
    {
        return extension_loaded('xcache') && 'cli' !== php_sapi_name();
    }
}
