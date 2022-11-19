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

use Stash\Interfaces\PoolInterface;
/*
 * Rough fix for SessionHandlerInterface difference in gc
 * return type after version PHP 8.0.0.
 * To be compatible with PHP 7.x versions we fixed return type
 * and suppress depreciation warning
*/
if (version_compare(PHP_VERSION,"8.0.0",">=")) {
	$__ERROR_REPORTING_FIXED = error_reporting(E_ALL ^ E_DEPRECATED);
}

/**
 * Stash\Session lets developers use Stash's Pool class to back session storage.
 * By injecting a Pool class into a Session object, and registering that Session
 * with PHP, developers can utilize any of Stash's drivers (including the
 * composite driver) and special features.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Session implements \SessionHandlerInterface
{
    /**
     * The Stash\Pool generates the individual cache items corresponding to each
     * session. Basically all persistence is handled by this object.
     *
     * @var Stash\Pool
     */
    protected $pool;

    /**
     * PHP passes a "save_path", which is not really relevant to most session
     * systems. This class uses it as a namespace instead.
     *
     * @var string
     */
    protected $path = '__empty_save_path';

    /**
     * The name of the current session, used as part of the cache namespace.
     *
     * @var string
     */
    protected $name = '__empty_session_name';

    /**
     * Some options (such as the ttl of a session) can be set by the developers.
     *
     * @var array
     */
    protected $options = array();

    /**
     * Registers a Session object with PHP as the session handler. 
     *
     * @param  \Stash\Session $handler
     * @return bool
     */
    public static function registerHandler(Session $handler): bool
    {
        return session_set_save_handler($handler, true);
    }

    /**
     * The constructor expects an initialized Pool object. The creation of this
     * object is up to the developer, but it should contain it's own unique
     * drivers or be appropriately namespaced to avoid conflicts with other
     * libraries.
     *
     * @param Interfaces\PoolInterface|Pool $pool
     */
    public function __construct(PoolInterface $pool)
    {
        $this->pool = $pool;
        $this->options['ttl'] = (int) ini_get('session.gc_maxlifetime');
    }

    /**
     * Options can be set using an associative array. The only current option is
     * a "ttl" value, which represents the amount of time (in seconds) that each
     * session should last.
     *
     * @param  array $options
     * @return bool
     */
    public function setOptions($options = array()) : void
    {
        $this->options = array_merge($this->options, $options);
    }

    /*
     * The functions below are all implemented according to the
     * SessionHandlerInterface interface.
     */

    /**
     * This function is defined by the SessionHandlerInterface and is for PHP's
     * internal use. It takes the saved session path and turns it into a
     * namespace.
     *
     * @param  string $save_path
     * @param  string $session_name
     * @return bool
     */
    public function open($save_path, $session_name) : bool
    {
        if (isset($save_path) && $save_path !== '') {
            $this->path = $save_path;
        }

        if (isset($session_name) || $session_name == '') {
            $this->name = $session_name;
        }

        return true;
    }


    protected function getCache($session_id) : \Stash\Item
    {
        $path = '/' .
            base64_encode($this->path) . '/' .
            base64_encode($this->name) . '/' .
            base64_encode($session_id);

        return $this->pool->getItem($path);
    }

    /**
     * This function is defined by the SessionHandlerInterface and is for PHP's
     * internal use. It reads the session data from the caching system.
     *
     * @param  string $session_id
     * @return string
     */
    public function read($session_id) : string
    {
        $cache = $this->getCache($session_id);
        $data = $cache->get();

        return $cache->isMiss() ? '' : $data;
    }

    /**
     * This function is defined by the SessionHandlerInterface and is for PHP's
     * internal use. It writes the session data to the caching system.
     *
     * @param  string $session_id
     * @param  string $session_data
     * @return bool
     */
    public function write($session_id, $session_data) : bool
    {
        $cache = $this->getCache($session_id);

        return $cache->set($session_data)->expiresAfter($this->options['ttl'])->save();
    }

    /**
     * This function is defined by the SessionHandlerInterface and is for PHP's
     * internal use. It currently does nothing important, as there is no need to
     * take special action.
     *
     * @return bool
     */
    public function close() : bool
    {
        return true;
    }

    /**
     * This function is defined by the SessionHandlerInterface and is for PHP's
     * internal use. It clears the current session.
     *
     * @param  string $session_id
     * @return bool
     */
    public function destroy($session_id) : bool
    {
        $cache = $this->getCache($session_id);

        return $cache->clear();
    }

    /**
     * This function is defined by the SessionHandlerInterface and is for PHP's
     * internal use. It is called randomly based on the session.gc_divisor,
     * session.gc_probability and session.gc_lifetime settings, which should be
     * set according to the drivers used. Those with built in eviction
     * mechanisms will not need this functionality, while those without it will.
     * It is also possible to disable the built in garbage collection (place
     * gc_probability as zero) and call the "purge" function on the Stash\Pool
     * class directly.
     *
     * @param  int|false  $maxlifetime
     * @return bool
     */
	//#[ReturnTypeWillChange]
	public function gc($maxlifetime)
    {
        return $this->pool->purge();
    }
}

/*
 * Restore error reporting to previous state
 */
if (isset($__ERROR_REPORTING_FIXED)) {
	error_reporting($__ERROR_REPORTING_FIXED);
}