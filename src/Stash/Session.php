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
    protected $namespace;
    
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    public function close()
    {
        return true;
    }

    public function destroy($session_id)
    {
        $cache = $this->pool->getCache($session_id);
        return $cache->clear();
    }

    public function gc($maxlifetime)
    {
        return $this->pool->purge();
    }

    public function open($save_path, $session_id)
    {
        $this->namespace = md5($save_path);
        return true;
    }

    public function read($session_id)
    {
        $cache = $this->pool->getCache($session_id);
        return (!$cache->isMiss()) ? $cache->get($session_data) : '';
    }

    public function write($session_id, $session_data)
    {
        $cache = $this->pool->getCache($session_id);
        return $cache->store($session_data);
    }

}