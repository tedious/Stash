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

use Stash\Driver\DriverInterface;
use Stash\Exception\Exception;
use Stash\Exception\InvalidArgumentException;

/**
 * Stash caches data that has a high generation cost, such as template preprocessing or code that requires a database
 * connection. This class can store any native php datatype, as long as it can be serialized (so when creating classes
 * that you wish to store instances of, remember the __sleep and __wake magic functions).
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Item
{
    const SP_NONE         = 0;
    const SP_OLD          = 1;
    const SP_VALUE        = 2;
    const SP_SLEEP        = 3;
    const SP_PRECOMPUTE   = 4;

    /**
     * This is the default time, in seconds, that objects are cached for.
     *
     * @var int seconds
     */
    static public $cacheTime = 432000; // five days

    /**
     * Disables the cache system wide. It is used internally when the storage engine fails or if the cache is being
     * cleared. This differs from the cacheEnabled property in that it affects all instances of the cache, not just one.
     *
     * @var bool
     */
    static $runtimeDisable = false;

    /**
     * Running count of how many times the cache has been called.
     *
     * @var int
     */
    static $cacheCalls = 0;

    /**
     * Running count of how many times the cache was able to successfully retrieve current data from the
     * cache. Combined with the $cacheCalls static variable this can be used to calculate a hit/miss ratio.
     *
     * @var int
     */
    static $cacheReturns = 0;

    /**
     * Keeps track of how many times a specific cache item is called. The array index is the string version of the key
     * and the value is the number of times it has been called.
     *
     * @var array
     */
    static $queryRecord;

    /**
     * Used internally to mark the class as disabled. Unlike the static runtimeDisable flag this is effective only for
     * the current instance.
     *
     * @var bool
     */
    protected $cacheEnabled = true;

    /**
     * Contains a list of default arguments for when users do not supply them.
     *
     * @var array
     */
    protected $defaults = array('precompute_time' => 40, // time, in seconds, before expiration
                                'sleep_time' => 500, // time, in microseconds, to sleep
                                'sleep_attempts' => 1, // number of times to sleep, wake up, and recheck cache
                                'stampede_ttl' => 30, // How long a stampede flag will be acknowledged
    );

    /**
     * The identifier for the item being cached. It is set through the setupKey function.
     *
     * @var array One dimensional array representing the location of a cached object.
     */
    protected $key;

    /**
     * A serialized version of the key, used primarily used as the index in various arrays.
     *
     * @var string
     */
    protected $keyString;

    /**
     * Marks whether or not stampede protection is enabled for this instance of Stash.
     *
     * @var bool
     */
    protected $stampedeRunning = false;

    /**
     * The cacheDriver being used by the system. While this class handles all of the higher functions, it's the cache
     * driver here that handles all of the storage/retrieval functionality. This value is set by the constructor.
     *
     * @var Stash\Driver\DriverInterface
     */
    protected $driver;

    /**
     * This is a flag to see if a valid response is returned. It is set by the getData function and is used by the
     * isMiss function.
     *
     * @var bool
     */
    private $isHit = null;

    /**
     * This constructor is an internal function used by the Pool object when
     * creating new Item objects. It should not be called directly.
     *
     * @internal
     * @param DriverInterface If no driver is passed the cache is set to script time only.
     */
    public function __construct(DriverInterface $driver, $key)
    {
        $this->driver = $driver;
        $this->setupKey($key);
    }

    /**
     * This disables any IO operations by this object, effectively preventing
     * the reading and writing of new data.
     *
     * @return bool
     */
    public function disable()
    {
        $this->cacheEnabled = false;
        return true;
    }

    /**
     * Returns the key as a string. This is particularly useful when the Item is
     * returned as a group of Items in an Iterator.
     *
     * @return string|bool Returns false if no key is set.
     */
    public function getKey()
    {
        return isset($this->keyString) ? $this->keyString : false;
    }

    /**
     * Clears the current Item. If hierarchical or "stackable" caching is being
     * used this function will also remove children Items.
     *
     * @return bool
     */
    public function clear()
    {
        try {
            return $this->executeClear();
        } catch (Exception $e) {
            $this->disable();
            return false;
        }
    }

    private function executeClear()
    {
        if ($this->isDisabled()) {
            return false;
        }

        return $this->driver->clear(isset($this->key) ? $this->key : null);
    }

    /**
     * Returns the data retrieved from the cache. Since this can return false or
     * null as a correctly cached value, the return value should not be used to
     * determine successful retrieval of data- for that use the "isMiss()"
     * function after call this one. If no value is stored at all then this
     * function will return null.
     *
     * @return mixed|null
     */
    public function get($invalidation = 0, $arg = null, $arg2 = null)
    {
        try {
            return $this->executeGet($invalidation, $arg, $arg2);
        } catch (Exception $e) {
            $this->disable();
            return null;
        }
    }

    private function executeGet($invalidation, $arg, $arg2)
    {
        self::$cacheCalls++;

        $this->isHit = false;

        if ($this->isDisabled()) {
            return null;
        }

        if (!isset($this->key)) {
            return null;
        }

        if (!is_array($invalidation)) {
            $vArray = array();

            if (isset($invalidation)) {
                $vArray[] = $invalidation;
            }

            if (isset($arg)) {
                $vArray[] = $arg;
            }

            if (isset($arg2)) {
                $vArray[] = $arg2;
            }

            $invalidation = $vArray;
        }

        $record = $this->getRecord();

        $this->validateRecord($invalidation, $record);

        if ($this->isHit) {
            self::$cacheReturns++;
            self::$queryRecord[$this->keyString][] = 1;
        } else {
            self::$queryRecord[$this->keyString][] = 0;
        }

        return isset($record['data']['return']) ? $record['data']['return'] : null;
    }

    /**
     * Returns true if the cached item needs to be refreshed.
     *
     * @return bool
     */
    public function isMiss()
    {
        if (!isset($this->isHit))
            $this->get();

        if ($this->isDisabled()) {
            return true;
        }

        return !$this->isHit;
    }

    /**
     * Enables stampede protection by marking this specific instance of the Item
     * as the one regenerating the cache.
     *
     * @return bool
     */
    public function lock($ttl = null)
    {
       if ($this->isDisabled()) {
            return true;
        }

        if (!isset($this->key)) {
            return false;
        }

        $this->stampedeRunning = true;

        $expiration = isset($ttl) && is_numeric($ttl) ? (int)$ttl : $this->defaults['stampede_ttl'];


        $spkey = $this->key;
        $spkey[0] = 'sp';
        return $this->driver->storeData($spkey, true, time() + $expiration);
    }

    /**
     * Takes and stores data for later retrieval. This data can be any php data,
     * including arrays and object, except resources and objects which are
     * unable to be serialized.
     *
     * @param mixed $data bool
     * @param int|DateTime|null $ttl Int is time (seconds), DateTime a future expiration date
     * @return bool Returns whether the object was successfully stored or not.
     */
    public function set($data, $ttl = null)
    {
        try {
            return $this->executeSet($data, $ttl);
        } catch (Exception $e) {
            $this->disable();
            return false;
        }
    }

    private function executeSet($data, $time)
    {
        if ($this->isDisabled()) {
            return false;
        }

        if (!isset($this->key)) {
            return false;
        }

        $store['return'] = $data;
        $store['createdOn'] = time();

        if (isset($time)) {
            if ($time instanceof \DateTime) {
                $expiration = $time->getTimestamp();
                $cacheTime = $expiration - $store['createdOn'];
            } else {
                $cacheTime = isset($time) && is_numeric($time) ? $time : self::$cacheTime;
            }
        } else {
            $cacheTime = self::$cacheTime;
        }

        $expiration = $store['createdOn'] + $cacheTime;

        if ($cacheTime > 0) {
            $diff = $cacheTime * 0.15;
            $expirationDiff = rand(0, floor($cacheTime * .15));
            $expiration -= $expirationDiff;
        }

        if ($this->stampedeRunning == true) {
            $spkey = $this->key;
            $spkey[0] = 'sp'; // change "cache" data namespace to stampede namespace
            $this->driver->clear($spkey);
            $this->stampedeRunning = false;
        }

        return $this->driver->storeData($this->key, $store, $expiration);
    }

    /**
     * Extends the expiration on the current cached item. For some engines this
     * can be faster than storing the item again.
     *
     * @return bool
     */
    public function extendCache()
    {
        if ($this->isDisabled()) {
            return false;
        }

        return $this->set($this->get());
    }

    /**
     * Return true if caching is disabled
     */
    public function isDisabled()
    {
        return self::$runtimeDisable
                || !$this->cacheEnabled
                || (defined('STASH_DISABLE_CACHE') && STASH_DISABLE_CACHE);
    }


    /**
     * Returns true is another Item is currently recalculating the cache.
     *
     * @return bool
     */
    protected function getStampedeFlag($key)
    {
        $key[0] = 'sp'; // change "cache" data namespace to stampede namespace
        $spReturn = $this->driver->getData($key);
        $sp = isset($spReturn['data']) ? $spReturn['data'] : false;


        if (isset($spReturn['expiration'])) {
            if ($spReturn['expiration'] < time()) {
                $sp = false;
            }
        }
        return $sp;
    }

    /**
     * Returns the record for the current key, whether that record is pulled from memory or a driver. If there is no
     * record than an empty array is returned.
     *
     * @return array
     */
    protected function getRecord()
    {
        $record = $this->driver->getData($this->key);

        if (!is_array($record)) {
            return array();
        }

        return $record;
    }

    /**
     * Decides whether the current data is fresh according to the supplied validation technique. As some techniques
     * actively change the record this function takes that in as a reference.
     *
     * @param array $validation
     * @param array $record
     */
    protected function validateRecord($validation, &$record)
    {
        if (is_array($validation)) {
            $argArray = $validation;
            $invalidation = isset($argArray[0]) ? $argArray[0] : 0;

            if (isset($argArray[1])) {
                $arg = $argArray[1];
            }

            if (isset($argArray[2])) {
                $arg2 = $argArray[2];
            }
        }

        $curTime = microtime(true);

        if (isset($record['expiration']) && ($ttl = $record['expiration'] - $curTime) > 0) {
            $this->isHit = true;

            if ($invalidation == self::SP_PRECOMPUTE) {
                $time = isset($arg) && is_numeric($arg) ? $arg : self::$defaults['precompute_time'];

                // If stampede control is on it means another cache is already processing, so we return
                // true for the hit.
                if ($ttl < $time) {
                    $this->isHit = (bool)$this->getStampedeFlag($this->key);
                }
            }

            return;

        }

        if (!isset($invalidation) || $invalidation == self::SP_NONE) {
            $this->isHit = false;
            return;
        }

        if (!$this->getStampedeFlag($this->key)) {
            $this->isHit = false;
            return;
        }

        switch ($invalidation) {
            case self::SP_VALUE:
                $record['data']['return'] = $arg;
                $this->isHit = true;
                break;

            case self::SP_SLEEP:
                $time = isset($arg) && is_numeric($arg) ? $arg : self::$defaults['sleep_time'];
                $attempts = isset($arg2) && is_numeric($arg2) ? $arg2 : self::$defaults['sleep_attempts'];

                $ptime = $time * 1000;

                if ($attempts <= 0) {
                    $this->isHit = false;
                    $record['data']['return'] = null;
                    break;
                }

                usleep($ptime);
                $record['data']['return'] = $this->get(self::SP_SLEEP, $time, $attempts - 1);
                break;

            case self::SP_OLD:
                $this->isHit = true;
                break;

            default:
            case self::SP_NONE:
                $this->isHit = false;
                break;
        } // switch($invalidate)
    }

    /**
     * This function is used by the Pool object while creating this object. It
     * is an internal function an should not be called directly.
     *
     * @internal
     * @param string|array $key
     */
    protected function setupKey($key)
    {
        if (is_array($key)) {
            $this->keyString = implode('/', $key);
        } else {
            $this->keyString = $key;
            $key = trim($key, '/');
            $key = explode('/', $key);
        }

        // We implant the namespace "cache" to the front of every stash object's key. This allows us to segment
        // off the user data, and user other 'namespaces' for internal purposes.
        array_unshift($key, 'cache');
        $this->key = array_map('strtolower', $key);
    }

}
