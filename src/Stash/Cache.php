<?php
/**
 * Stash
 *
 * Copyright (c) 2009-2011, Robert Hafner <tedivm@tedivm.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Robert Hafner nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Stash
 * @author     Robert Hafner <tedivm@tedivm.com>
 * @copyright  2009-2011 Robert Hafner <tedivm@tedivm.com>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://code.google.com/p/stash/
 * @since      File available since Release 0.9.1
 * @version    Release: 0.9.5
 */

namespace Stash;

use Stash\Handler\HandlerInterface;
use Stash\Exception\Exception;
use Exception as BaseException;

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
 * @author Robert Hafner <tedivm@tedivm.com>
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
    protected $memoryOnly = false;

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
        $this->handler = $handler;
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
    public function storeInMemoryOnly($memoryOnly = true)
    {
        $this->memoryOnly = (boolean) $memoryOnly;
        return true;
    }

    public function isMemoryOnly()
    {
        return $this->handler === null || $this->memoryOnly || (defined('STASH_FORCE_MEM_ONLY') && STASH_FORCE_MEM_ONLY);
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
        try {
            return $this->executeClear();
        } catch (BaseException $e) {
            $this->disable();
            return false;
        }
    }

    private function executeClear()
    {
        if ($this->isDisabled()) {
            return false;
        }

        if (!$this->isMemoryOnly()) {
            self::$memStore[$this->group] = array();
            return $this->handler->clear(isset($this->key) ? $this->key : null);
        }

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

        return true;
    }

    /**
     * Removes all expired or stale data from the cache system. It may also perform other cleanup actions depending on
     * the cache handler used.
     *
     * @return bool
     */
    public function purge()
    {
        try {
            return $this->executePurge();
        } catch (BaseException $e) {
            $this->disable();
            return false;
        }
    }

    private function executePurge()
    {
        if ($this->isDisabled()) {
            return false;
        }

        self::$memStore[$this->group] = array();

        if ($this->isMemoryOnly()) {
            return true;
        }

        return $this->handler->purge();
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
        try {
            return $this->executeGet($invalidation, $arg, $arg2);
        } catch (BaseException $e) {
            $this->disable();
            return null;
        }
    }

    private function executeGet($invalidation, $arg, $arg2)
    {
        self::$cacheCalls++;

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
        if ($this->isDisabled()) {
            return true;
        }

        return !$this->isHit;
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

        if ($this->isMemoryOnly()) {
            return true;
        }

        if (!isset($this->key)) {
            return false;
        }

        $this->stampedeRunning = true;

        $expiration = isset($ttl) && is_numeric($ttl) ? (int)$ttl : $this->defaults['stampede_ttl'];


        $spkey = $this->key;
        $spkey[0] = 'sp';
        return $this->handler->storeData($spkey, true, time() + $expiration);
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
        try {
            return $this->executeStore($data, $time);
        } catch (BaseException $e) {
            $this->disable();
            return false;
        }
    }

    private function executeStore($data, $time)
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

        if ($this->storeMemory) {
            self::$memStore[$this->group][$this->keyString] = array('expiration' => $expiration, 'data' => $store);
        }

        if ($this->isMemoryOnly()) {
            return true;
        }

        if ($this->stampedeRunning == true) {
            $spkey = $this->key;
            $spkey[0] = 'sp';
            $this->handler->clear($spkey);
            $this->stampedeRunning = false;
        }

        return $this->handler->storeData($this->key, $store, $expiration);
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

        return $this->store($this->get());
    }

    /**
     * Returns true is another instance of Stash is currently recalculating the cache.
     *
     * @return bool
     */
    protected function getStampedeFlag($key)
    {
        if ($this->isMemoryOnly()) {
            return false;
        }

        $key[0] = 'sp';
        $spReturn = $this->handler->getData($key);
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
            return self::$memStore[$this->group][$this->keyString];
        }

        if ($this->isMemoryOnly()) {
            return array();
        }

        $record = $this->handler->getData($this->key);

        if (!is_array($record)) {
            return array();
        }

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
