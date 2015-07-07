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

/**
 * The ephemeral class exists to assist with testing the main Stash class. Since this is a very minimal driver we can
 * test Stash without having to worry about underlying problems interfering.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Ephemeral extends AbstractDriver
{
    /**
     * Contains the cached data.
     *
     * @var array
     */
    protected $store = array();

    /**
     * {@inheritdoc}
     */
    public function getData($key)
    {
        $key = $this->getKeyIndex($key);

        return isset($this->store[$key]) ? $this->store[$key] : false;
    }

    /**
     * Converts the key array into a passed function
     *
     * @param  array  $key
     * @return string
     */
    protected function getKeyIndex($key)
    {
        $index = '';
        foreach ($key as $value) {
            $index .= str_replace('#', '#:', $value) . '#';
        }

        return $index;
    }

    /**
     * {@inheritdoc}
     */
    public function storeData($key, $data, $expiration)
    {
        $this->store[$this->getKeyIndex($key)] = array('data' => $data, 'expiration' => $expiration);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($key = null)
    {
        if (!isset($key)) {
            $this->store = array();
        } else {
            $clearIndex = $this->getKeyIndex($key);
            foreach ($this->store as $index => $data) {
                if (strpos($index, $clearIndex) === 0) {
                    unset($this->store[$index]);
                }
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        $now = time();
        foreach ($this->store as $index => $data) {
            if ($data['expiration'] <= $now) {
                unset($this->store[$index]);
            }
        }

        return true;
    }
}
