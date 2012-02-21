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
 * @subpackage Handlers
 * @author     Robert Hafner <tedivm@tedivm.com>
 * @copyright  2009-2011 Robert Hafner <tedivm@tedivm.com>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://code.google.com/p/stash/
 * @since      File available since Release 0.9.1
 * @version    Release: 0.9.5
 */

namespace Stash\Handler;

use Stash;
use Stash\Handler\Sub\Memcache as SubMemcache;
use Stash\Handler\Sub\Memcached as SubMemcached;
use Stash\Exception\MemcacheException;

/**
 * Memcache is a wrapper around the popular memcache server. Memcache supports both memcache php
 * extensions and allows access to all of their options as well as all Stash features (including hierarchical caching).
 *
 * @package Stash
 * @author Robert Hafner <tedivm@tedivm.com>
 */
class Memcache implements HandlerInterface
{
    /**
     * Memcache subhandler used by this class.
     *
     * @var SubMemcache|SubMemcached
     */
    protected $memcache;

    /**
     *
     * * servers - An array of servers, with each server represented by its own array (array(host, port, [weight])). If
     * not passed the default is array('127.0.0.1', 11211).
     *
     * * extension - Which php extension to use, either 'memcache' or 'memcache'. Defaults to memcache with memcache
     * as a fallback.
     *
     * * Options can be passed to the "memcache" handler by adding them to the options array. The memcache extension
     * defined options using contants, ie Memcached::OPT_*. By passing in the * portion ('compression' for
     * Memcached::OPT_COMPRESSION) and its respective option. Please see the php manual for the specific options
     * (http://us2.php.net/manual/en/memcache.constants.php)
     *
     * @param array $options
     */
    public function __construct($options = array())
    {
        if (!isset($options['servers'])) {
            $options['servers'] = array('127.0.0.1', 11211);
        }

        if (!is_array($options['servers'])) {
            throw new MemcacheException('Server list required to be an array.');
        }

        if (is_scalar($options['servers'][0])) {
            $servers = array($options['servers']);
        } else {
            $servers = $options['servers'];
        }


        if (!isset($options['extension'])) {
            $options['extension'] = 'any';
        }

        $extension = strtolower($options['extension']);

        if (class_exists('Memcached', false) && $extension != 'memcache') {
            $this->memcache = new SubMemcached();
        } elseif (class_exists('Memcache', false) && $extension != 'memcached') {
            $this->memcache = new SubMemcache();
        } else {
            throw new MemcacheException('Unable to load either memcache extension.');
        }

        if ($this->memcache->initialize($servers, $options)) {
            return;
        }
    }

    /**
     * Empty destructor to maintain a standardized interface across all handlers.
     *
     */
    public function __destruct()
    {
    }

    /**
     *
     * @return array
     */
    public function getData($key)
    {
        $keyString = $this->makeKeyString($key);
        return $this->memcache->get($keyString);
    }

    /**
     *
     * @param array $data
     * @param int $expiration
     * @return bool
     */
    public function storeData($key, $data, $expiration)
    {
        $keyString = $this->makeKeyString($key);
        return $this->memcache->set($keyString, $data, $expiration);
    }

    /**
     *
     * @param null|array $key
     * @return bool
     */
    public function clear($key = null)
    {
        $this->keyCache = array();
        if (is_null($key)) {
            $this->memcache->flush();
        } else {
            $keyString = $this->makeKeyString($key, true);
            $this->memcache->inc($keyString);
            $this->keyCache = array();
            $this->makeKeyString($key);
        }
        $this->keyCache = array();
        return true;
    }

    /**
     *
     * @return bool
     */
    public function purge()
    {
        return true;
    }

    protected function makeKeyString($key, $path = false)
    {
        // array(name, sub);
        // a => name, b => sub;

        $key = \Stash\Utilities::normalizeKeys($key);

        $keyString = 'cache:::';
        foreach ($key as $name) {
            //a. cache:::name
            //b. cache:::name0:::sub
            $keyString .= $name;

            //a. :pathdb::cache:::name
            //b. :pathdb::cache:::name0:::sub
            $pathKey = ':pathdb::' . $keyString;
            $pathKey = md5($pathKey);

            if (isset($this->keyCache[$pathKey])) {
                $index = $this->keyCache[$pathKey];
            } else {
                $index = $this->memcache->cas($pathKey, 0);
                $this->keyCache[$pathKey] = $index;
            }

            //a. cache:::name0:::
            //b. cache:::name0:::sub1:::
            $keyString .= '_' . $index . ':::';
        }

        return $path ? $pathKey : md5($keyString);
    }

    public function canEnable()
    {
        return $this->memcache->canEnable();
    }
}
