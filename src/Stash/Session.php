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

use Stash\Pool;

/**
 *
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class SessionHandler implements SessionHandlerInterface
{
    protected $pool;
    protected $path;
    protected $options = array();
    
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
        $this->options['ttl'] = (int) ini_get('session.gc_maxlifetime');        
    }
    
    public function setOptions($options = array())
    {
        $this->options = array_merge_recursive($this->options, $options);
    }
    
    public function open($save_path, $session_id)
    {
        $this->path = md5($save_path) . '/';
        return true;
    }
    
    public function read($session_id)
    {
        $cache = $this->pool->getCache($this->path . $session_id);
        return (!$cache->isMiss()) ? $cache->get() : '';
    }

    public function write($session_id, $session_data)
    {
        $id = $this->path . $session_id;
        $cache = $this->pool->getCache($this->path . $session_id);
        return $cache->store($session_data, $this->options['ttl']);
    }
    
    public function close()
    {
        return true;
    }

    public function destroy($session_id)
    {
        $cache = $this->pool->getCache($this->path . $session_id);
        return $cache->clear();
    }

    public function gc($maxlifetime)
    {
        return $this->pool->purge();
    }

}