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

use Stash\Interfaces\DriverInterface;

/**
 * This class provides a NULL caching driver, it always takes values, but never saves them
 * Can be used as an default save driver
 *
 * @author Benjamin Zikarsky <benjamin.zikarsky@perbility.de>
 */
class BlackHole implements DriverInterface
{

    /**
     * NOOP constructor
     */
    public function setOptions(array $options = array())
    {
        // empty
    }

    /**
     * {@inheritdoc}
     */
    public function clear($key = null)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getData($key)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function storeData($key, $data, $expiration)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function isAvailable()
    {
        return true;
    }

}
