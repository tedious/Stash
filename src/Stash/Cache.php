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

use Stash\Handler\HandlerInterface;
use Stash\Exception\Exception;

/**
 * Stash caches data that has a high generation cost, such as template preprocessing or code that requires a database
 * connection. This class can store any native php datatype, as long as it can be serialized (so when creating classes
 * that you wish to store instances of, remember the __sleep and __wake magic functions).
 *
 * * * * * * * * * * * *
 * Creating Stash Object
 * * *
 *
 * <code>
 * // Create backend handler
 * $handler = new StashFileSystem();
 *
 * // Create Stash object and inject handler.
 * $stash = new Stash($handler);
 *
 * // Setup Key
 * $stash->setupKey('Path', 'To', 'Item');
 * </code>
 *
 * This can also be accomplished using one of the Stash wrappers, like StashBox.
 *
 * * * * * * * *
 * Getting and Storing Data
 * * *
 *
 * <code>
 *  // Create backend handler
 * $handler = new StashFileSystem();
 *
 * // Set backend handler - this only has to be done once!
 * StashBox::setHandler($handler);
 *
 * // Get Stash object, including optional key.
 * $stash = StashBox::getCache('Path', 'To', 'Item');
 *
 * // Get another, new Stash object without having to set a new handler.
 * $otherStash = StashBox::getCache('Object', 'Stored');
 * </code>
 *
 * Using Stash is a simple process of getting data, checking if it is stale, and then storing the recalculated data
 * if it was.
 *
 * <code>
 * // Grab a fresh Stash item.
 * $stash = StashBox::getCache('path', 'to', 'the','item');
 *
 * // Pull the data from the cache.
 * $data = $stash->get();
 *
 * // Check to see if the data is stale or didn't return at all.
 * if($stash->isMiss())
 * {
 *   // Run all the long running code.
 *      $data = runExpensiveCode();
 *
 *      // Save the code for later.
 *      $stash->store($data);
 * }
 * </code>
 *
 * * * * * * * * *
 * Clearing Data
 * * *
 *
 * Clearing data is very similar to getting it.
 * <code>
 * $handler = new StashFileSystem();
 * $stash = new Stash($handler);
 * $stash->setupKey('path', 'to', item');
 * $stash->clear();
 * </code>
 *
 * The wrappers, like StashBox, offer a one function call to clear data.
 * <code>
 * $stash = StashBox::clearCache('path', 'to', 'item');
 *
 * // Clear out everything in the 'path' node, including 'path' and 'path' 'to' 'item'.
 * $stash = StashBox::clearCache('path');
 *
 * // Clear out everything in the cache.
 * $stash = StashBox::clearCache();
 * </code>
 *
 * * * * * * * *
 * Purging Stale Data
 * * *
 *
 * Running the purge function cleans out any stale data, lowering the size of the cache pool. It also allows the
 * handlers to run their own handler specific cleanup functions. For larger caches this function can take quite a bit
 * of time, so it is best run in its own cleanup process.
 *
 * <code>
 * $handler = new StashFileSystem();
 * $stash = new Stash($handler);
 * $stash->purge();
 * </code>
 *
 * The wrappers, like StashBox, offer a one function call to clear data.
 * <code>
 * StashBox::purgeCache();
 * </code>
 *
 *
 *
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Cache
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
     * Disbles the cache system wide. It is used internally when the storage engine fails or if the cache is being
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
     * Holds a copy of all valid data (whether retrieved from or stored to the cacheHandler) in order to
     * avoid unnecessary calls to the storage handler. The index of this array is the string version of the key, and
     * the value is an exact copy of the data stored by the handlers. When items are added or removed this array gets
     * updated automatically. It also gets purged over time to keep it from growing to large.
     *
     * @var string
     */
    protected static $memStore = array();

    /**
     * If set to true, the system stores a copy of the current cache data (key, data and expiration) stored to a static
     * variable. This allows future requests to that object to bypass retriving it from the cachehandler, but the trade
     * off is that scripts use a bit more memory. For large pieces of data not likely to be called multiple times in a
     * script (template data, for instance) this should be set to false.
     *
     * @var bool
     */
    protected $storeMemory = true;

    /**
     * If this flag is set to true the cache record is only stored in the scripts memory, not persisted. If this is
     * true and the storeMemory property is false then caching is effectively disabled.
     *
     * @var bool
     */
    protected $memOnly = false;

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
                                'stampede_ttl' => 30, // How long a stampede flag will be aknowledged
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
     * The cacheHandler being used by the system. While this class handles all of the higher functions, it's the cache
     * handler here that handles all of the storage/retrieval functionality. This value is set by the constructor.
     *
     * @var cacheHandler
     */
    protected $handler;

    /**
     * This is a flag to see if a valid response is returned. It is set by the getData function and is used by the
     * isMiss function.
     *
     * @var bool
     */
    private $isHit = false;

    protected $group;

    /**
     * This constructor requires a StashHandler object.
     *
     * @param HandlerInterface If no handler is passed the cache is set to script time only.
     */
    public function __construct(HandlerInterface $handler = null, $cacheGroup = null)
    {
        if (!isset($cacheGroup)) {
            $cacheGroup = '__global';
            if (isset($handler)) {
                $cacheGroup .= '_' . get_class($handler);
            }

            $cacheGroup .= '__';
        }

        $this->group = $cacheGroup;

        if ((defined('STASH_FORCE_MEM_ONLY') && STASH_FORCE_MEM_ONLY) || !isset($handler)) {
            $this->memOnly = true;
        } else {
            $this->handler = $handler;
        }
    }

    /**
     * Disables the specific instance of the cache handler. This makes it simpler to embed the cache handling code in
     * places where it may not always want the results stored.
     *
     * @return bool
     */
    public function disable()
    {
        $this->cacheEnabled = false;
        return true;
    }

    /**
     * Tells the cache handler not to store the results of this particular request in the memory storage.
     *
     * @return bool
     */
    public function disableMemory()
    {
        $this->storeMemory = false;
        return true;
    }

    /**
     * Skips the cache handler and stores the result of this particular request only in the memory storage.
     *
     * @return bool
     */
    public function memOnly()
    {
        $this->memOnly = true;
        return true;
    }

    /**
     * Takes a virtually unlimited number of arguments which represent the key, or path, of a cached object. These
     * strings should be unique to the data you are being stored or retrieved and should be considered hierarchical-
     * that is, each additional argument passed is considered a child of the one before it by the system.
     * This function takes that passed data and uses it to create a key usable by the caching engines.
     *
     * @example $cache = new Cache('permissions', 'user', '4', '2'); where 4 is the user id and 2 is the location id.
     *
     * @param string $key, $key, $key...
     */
    public function setupKey()
    {
        if (func_num_args() == 0) {
            throw new Exception('No key sent to the cache constructor.');
        }

        $key = func_get_args();
        if (count($key) == 1 && is_array($key[0])) {
            $key = $key[0];
        }

        array_unshift($key, 'cache');

        $this->key = array_map('strtolower', $key);
        $this->keyString = implode(':::', $this->key);
    }

    /**
     * Clears the current Stash item and all of its children. If no key is set it clears the entire cache.
     *
     * @return bool
     */
    public function clear()
    {
        if ($this->isDisabled()) {
            return false;
        }

        try {
            if ($handler = $this->getHandler()) {
                self::$memStore[$this->group] = array();
                return $handler->clear(isset($this->key) ? $this->key : null);
            } else {

                // Typically speaking- when there is handler backing the Stash class- we just want to wipe the whole
                // memCache out when it's cleared. However, when it is the only 'backend' available it makes sense
                // to actually go through and only clear the items requested by the user, since any cache misses from
                // script memory are then complete misses.

                if (!isset($this->keyString)) {
                    self::$memStore[$this->group] = array();
                    return true;
                }

                $length = strlen($this->keyString);
                foreach (self::$memStore[$this->group] as $name => $value) {
                    if (substr($name, 0, $length) == $this->keyString) {
                        unset(self::$memStore[$this->group][$name]);
                    }
                }
            }

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Removes all expired or stale data from the cache system. It may also perform other cleanup actions depending on
     * the cache handler used.
     *
     * @return bool
     */
    public function purge()
    {
        if ($this->isDisabled()) {
            return false;
        }

        try {
            self::$memStore[$this->group] = array();
            if ($handler = $this->getHandler()) {
                return $handler->purge();
            }
            return true;
        } catch (\Exception $e) {

        }

        return false;
    }

    /**
     * Returns the data retrieved from the cache. Since this can return false or null as a correctly cached value, the
     * return value should not be used to determine successful retrieval of data- for that use the "isMiss()" function
     * after call this one. If no value is stored at all then this function will return null.
     *
     * @return mixed|null
     */
    public function get($invalidation = 0, $arg = null, $arg2 = null)
    {
        self::$cacheCalls++;

        if ($this->isDisabled()) {
            return null;
        }

        if (!isset($this->key) || !isset($this->key)) {
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

        try {
            $record = $this->getRecord();
            $this->validateRecord($invalidation, $record);

            if ($this->isHit) {
                self::$cacheReturns++;
                self::$queryRecord[$this->keyString][] = 1;
            } else {
                self::$queryRecord[$this->keyString][] = 0;
            }

            return isset($record['data']['return']) ? $record['data']['return'] : null;

        } catch (\Exception $e) {
            $this->cache_enabled = false;
            return null;
        }
    }

    /**
     * Returns true if the cached item needs to be refreshed.
     *
     * @return bool
     */
    public function isMiss()
    {
        if ($this->isDisabled()) {
            return true;
        }

        return !($this->isHit);
    }

    /**
     * Enables stampede protection by marking this specific instance of Stash as the one regenerating the cache.
     *
     * @return bool
     */
    public function lock($ttl = null)
    {
        if ($this->isDisabled()) {
            return true;
        }

        if (!isset($this->key) || !isset($this->key)) {
            return false;
        }

        if ($this->memOnly || !($handler = $this->getHandler())) {
            return true;
        }

        $this->stampedeRunning = true;

        $expiration = isset($ttl) && is_numeric($ttl) ? (int)$ttl : $this->defaults['stampede_ttl'];


        $spkey = $this->key;
        $spkey[0] = 'sp';
        return $handler->storeData($spkey, true, time() + $expiration);
    }

    /**
     * Takes and stores data for later retrieval. This data can be any php data, including arrays and object, except
     * resources and objects which are unable to be serialized.
     *
     * @param mixed $data bool
     * @param int|DateTime|null $time How long the item should be stored. Int is time (seconds), DateTime a future date
     * @return bool Returns whether the object was successfully stored or not.
     */
    public function store($data, $time = null)
    {
        if ($this->isDisabled()) {
            return false;
        }

        try {

            if (!isset($this->key) || !isset($this->key)) {
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

            if ($this->storeMemory) {
                self::$memStore[$this->group][$this->keyString] = array('expiration' => $expiration, 'data' => $store
                );
            }

            if ($this->memOnly || !($handler = $this->getHandler())) {
                return true;
            }

            if ($this->stampedeRunning == true) {
                $spkey = $this->key;
                $spkey[0] = 'sp';
                $handler->clear($spkey);
                $this->stampedeRunning = false;
            }

            return $handler->storeData($this->key, $store, $expiration);
        } catch (\Exception $e) {

        }
        return false;
    }

    /**
     * Extends the expiration on the current cached item. For some engines this can be faster than storing the item
     * again.
     *
     * @return bool
     */
    public function extendCache()
    {
        if ($this->isDisabled()) {
            return false;
        }

        $data = $this->get();

        if (!isset($data) && !@is_null($data)) {
            return false;
        }

        return $this->store($data);
    }

    /**
     * Returns the StashHandler in use by this class or false is none are set.
     *
     * @return HandlerInterface|boolean
     */
    protected function getHandler()
    {
        if ($this->isDisabled()) {
            return false;
        }

        if (isset($this->handler)) {
            return $this->handler;
        }

        return false;
    }


    /**
     * Returns true is another instance of Stash is currently recalculating the cache.
     *
     * @return bool
     */
    protected function getStampedeFlag($key)
    {
        if ($this->memOnly || !($handler = $this->getHandler())) {
            return false;
        }

        $key[0] = 'sp';
        $spReturn = $handler->getData($key);
        $sp = isset($spReturn['data']) ? $spReturn['data'] : false;


        if (isset($spReturn['expiration'])) {
            if ($spReturn['expiration'] < time()) {
                $sp = false;
            }
        }
        return $sp;
    }

    /**
     * Returns the record for the current key, whether that record is pulled from memory or a handler. If there is no
     * record than an empty array is returned.
     *
     * @return array
     */
    protected function getRecord()
    {
        if (isset(self::$memStore[$this->group][$this->keyString]) && is_array(self::$memStore[$this->group][$this->keyString])) {
            $record = self::$memStore[$this->group][$this->keyString];
        } elseif (!$this->memOnly) {

            $record = null;
            $handler = $this->getHandler();
            if ($handler = $this->getHandler()) {
                $record = $handler->getData($this->key);
            }

            if (!is_array($record)) {
                $record = array();
            } else {
                // This is to keep the array from getting out of hand, particularly during long running processes
                // as this would otherwise grow to huge amounts. Totally niave approach, will redo
                if (isset(self::$memStore[$this->group]) && count(self::$memStore[$this->group]) > 900) {
                    foreach (array_rand(self::$memStore[$this->group], 600) as $removalKey) {
                        unset(self::$memStore[$this->group][$removalKey]);
                    }
                }

                if ($this->storeMemory) {
                    self::$memStore[$this->group][$this->keyString] = $record;
                }
            }

        } else {
            $record = array();
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
     * Return true if caching is disabled
     */
    public function isDisabled()
    {
        return self::$runtimeDisable || !$this->cacheEnabled || (defined('STASH_DISABLE_CACHE') && STASH_DISABLE_CACHE);
    }
}
