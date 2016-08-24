<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test\Stubs;

use Stash;

class MemcachedStub extends \Memcached
{
    protected $options = [];
    protected $servers = [];

    public function __construct($isPersistent, $pristine)
    {
        // disable original constructor
    }

    public function addServer($host, $port, $weight = 0)
    {
        $this->addServers(array(array($host, $port, $weight)));
        return true;
    }

    public function addServers(array $servers)
    {
        $this->servers += $servers;
        return true;
    }

    public function getServerList()
    {
        return $this->servers;
    }

    public function setOptions($options)
    {
        $this->options = $options;
        return true;
    }

    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
        return true;
    }

    public function getOptions()
    {
        return $this->options;
    }
}
