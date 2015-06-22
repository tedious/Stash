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
use Stash\Exception\RuntimeException;

/**
 * Abstract base class for all drivers to use.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
abstract class AbstractDriver implements DriverInterface
{
    /**
     * Initializes the driver.
     *
     * @throws RuntimeException
     */
    public function __construct(array $options = array())
    {
        if (!static::isAvailable()) {
            throw new RuntimeException(__CLASS__ . ' is not available.');
        }
        $this->setOptions($options);
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options = array())
    {
        // empty
    }

    /**
     * {@inheritdoc}
     */
    public static function isAvailable()
    {
        return true;
    }
}