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

use Stash\Exception\Exception;
use Stash\Interfaces\DriverInterface;
use Stash\Interfaces\ItemInterface;
use Stash\Interfaces\PoolInterface;

/**
 * Stash caches data that has a high generation cost, such as template preprocessing or code that requires a database
 * connection. This class can store any native php datatype, as long as it can be serialized (so when creating classes
 * that you wish to store instances of, remember the __sleep and __wake magic functions).
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Item implements ItemInterface
{
    /**
     * @deprecated
     */
    const SP_NONE         = 0;

    /**
     * @deprecated
     */
    const SP_OLD          = 1;

    /**
     * @deprecated
     */
    const SP_VALUE        = 2;

    /**
     * @deprecated
     */
    const SP_SLEEP        = 3;

    /**
     * @deprecated
     */
    const SP_PRECOMPUTE   = 4;

    /**
     * This is the default time, in seconds, that objects are cached for.
     *
     * @var int seconds
     */
    public static $cacheTime = 432000; // five days

    /**
     * Disables the cache system wide. It is used internally when the storage engine fails or if the cache is being
     * cleared. This differs from the cacheEnabled property in that it affects all instances of the cache, not just one.
     *
     * @var bool
     */
    public static $runtimeDisable = false;

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
     * The Pool that spawned this instance of the Item..
     *
     * @var \Stash\Interfaces\PoolInterface
     */
    protected $pool;

    /**
     * The cacheDriver being used by the system. While this class handles all of the higher functions, it's the cache
     * driver here that handles all of the storage/retrieval functionality. This value is set by the constructor.
     *
     * @var \Stash\Interfaces\DriverInterface
     */
    protected $driver;

    /**
     * If set various then errors and exceptions will get passed to the PSR Compliant logging library. This
     * can be set using the setLogger() function in this class.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Defines the namespace the item lives in.
     *
     * @var string|null
     */
    protected $namespace = null;

    /**
     * This is a flag to see if a valid response is returned. It is set by the getData function and is used by the
     * isMiss function.
     *
     * @var bool
     */
    private $isHit = null;


    /**
     * {@inheritdoc}
     */
    public function setPool(PoolInterface $pool)
    {
        $this->pool = $pool;
        $this->setDriver($pool->getDriver());
    }

    /**
     * {@inheritdoc}
     */
    public function setKey($key, $namespace = null)
    {
        $this->namespace = $namespace;
        $this->setupKey($key);
    }

    /**
     * {@inheritdoc}
     */
    public function disable()
    {
        $this->cacheEnabled = false;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return isset($this->keyString) ? $this->keyString : false;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        try {
            return $this->executeClear();
        } catch (Exception $e) {
            $this->logException('Clearing cache caused exception.', $e);
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
     * {@inheritdoc}
     */
    public function get($invalidation = 0, $arg = null, $arg2 = null)
    {
        try {
            return $this->executeGet($invalidation, $arg, $arg2);
        } catch (Exception $e) {
            $this->logException('Retrieving from cache caused exception.', $e);
            $this->disable();

            return null;
        }
    }

    private function executeGet($invalidation, $arg, $arg2)
    {
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

        return isset($record['data']['return']) ? $record['data']['return'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function isMiss()
    {
        if (!isset($this->isHit)) {
            $this->get();
        }

        if ($this->isDisabled()) {
            return true;
        }

        return !$this->isHit;
    }

    /**
     * {@inheritdoc}
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

        $expiration = isset($ttl) && is_numeric($ttl) ? (int) $ttl : $this->defaults['stampede_ttl'];


        $spkey = $this->key;
        $spkey[0] = 'sp';

        return $this->driver->storeData($spkey, true, time() + $expiration);
    }

    /**
     * {@inheritdoc}
     */
    public function set($data, $ttl = null)
    {
        try {
            return $this->executeSet($data, $ttl);
        } catch (Exception $e) {
            $this->logException('Setting value in cache caused exception.', $e);
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

        $store = array();
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
     * {@inheritdoc}
     */
    public function extend($ttl = null)
    {
        if ($this->isDisabled()) {
            return false;
        }

        return $this->set($this->get(), $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function isDisabled()
    {
        return self::$runtimeDisable
                || !$this->cacheEnabled
                || (defined('STASH_DISABLE_CACHE') && STASH_DISABLE_CACHE);
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Sets the driver this object uses to interact with the caching system.
     *
     * @param DriverInterface $driver
     */
    protected function setDriver(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Logs an exception with the Logger class, if it exists.
     *
     * @param  string     $message
     * @param  \Exception $exception
     * @return bool
     */
    protected function logException($message, $exception)
    {
        if (!isset($this->logger)) {
            return false;
        }

        $this->logger->critical($message,
                                array('exception' => $exception,
                                      'key' => $this->keyString));

        return true;
    }

    /**
     * Returns true if another Item is currently recalculating the cache.
     *
     * @param  array $key
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
     * Returns the record for the current key. If there is no record than an empty array is returned.
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
     * This function has the ability to change the isHit property as well as the record passed.
     *
     * @internal
     * @param array $validation
     * @param array &$record
     */
    protected function validateRecord($validation, &$record)
    {
        if (is_array($validation)) {
            $argArray = $validation;
            $invalidation = isset($argArray[0]) ? $argArray[0] : Invalidation::PRECOMPUTE;

            if (isset($argArray[1])) {
                $arg = $argArray[1];
            }

            if (isset($argArray[2])) {
                $arg2 = $argArray[2];
            }
        } else {
            $invalidation = Invalidation::PRECOMPUTE;
        }

        $curTime = microtime(true);

        if (isset($record['expiration']) && ($ttl = $record['expiration'] - $curTime) > 0) {
            $this->isHit = true;

            if ($invalidation == Invalidation::PRECOMPUTE) {
                $time = isset($arg) && is_numeric($arg) ? $arg : $this->defaults['precompute_time'];

                // If stampede control is on it means another cache is already processing, so we return
                // true for the hit.
                if ($ttl < $time) {
                    $this->isHit = (bool) $this->getStampedeFlag($this->key);
                }
            }

            return;
        }

        if (!isset($invalidation) || $invalidation == Invalidation::NONE) {
            $this->isHit = false;

            return;
        }

        if (!$this->getStampedeFlag($this->key)) {
            $this->isHit = false;

            return;
        }

        switch ($invalidation) {
            case Invalidation::VALUE:
                if (!isset($arg)) {
                    $this->isHit = false;

                    return;
                } else {
                    $record['data']['return'] = $arg;
                    $this->isHit = true;
                }
                break;

            case Invalidation::SLEEP:
                $time = isset($arg) && is_numeric($arg) ? $arg : $this->defaults['sleep_time'];
                $attempts = isset($arg2) && is_numeric($arg2) ? $arg2 : $this->defaults['sleep_attempts'];

                $ptime = $time * 1000;

                if ($attempts <= 0) {
                    $this->isHit = false;
                    $record['data']['return'] = null;
                    break;
                }

                usleep($ptime);
                $record['data']['return'] = $this->get(Invalidation::SLEEP, $time, $attempts - 1);
                break;

            case Invalidation::OLD:
                $this->isHit = true;
                break;

            default:
            case Invalidation::NONE:
                $this->isHit = false;
                break;
        } // switch($invalidate)
    }

    /**
     * {@inheritdoc}
     */
    public function getCreation()
    {
        $record = $this->getRecord();
        if (!isset($record['data']['createdOn'])) {
            return false;
        }

        $dateTime = new \DateTime();
        $dateTime->setTimestamp($record['data']['createdOn']);

        return $dateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpiration()
    {
        $record = $this->getRecord();
        if (!isset($record['expiration'])) {
            return false;
        }

        $dateTime = new \DateTime();
        $dateTime->setTimestamp($record['expiration']);

        return $dateTime;
    }

    /**
     * This clears out any locks that are present if this Item is prematurely destructed.
     */
    public function __destruct()
    {
        if (isset($this->stampedeRunning) && $this->stampedeRunning == true) {
            $spkey = $this->key;
            $spkey[0] = 'sp';
            $this->driver->clear($spkey);
        }
    }

    /**
     * This function is used by the Pool object while creating this object. It
     * is an internal function an should not be called directly.
     *
     * @param  array                     $key
     * @throws \InvalidArgumentException
     */
    protected function setupKey($key)
    {
        if (!is_array($key)) {
            throw new \InvalidArgumentException('Item requires keys as arrays.');
        }

        $keyStringTmp = $key;
        if (isset($this->namespace)) {
            array_shift($keyStringTmp);
        }

        $this->keyString = implode('/', $keyStringTmp);

        // We implant the namespace "cache" to the front of every stash object's key. This allows us to segment
        // off the user data, and use other 'namespaces' for internal purposes.
        array_unshift($key, 'cache');
        $this->key = array_map('strtolower', $key);
    }
}
