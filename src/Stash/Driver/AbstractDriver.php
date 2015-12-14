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
     * @param array $options
     *   An additional array of options to pass through to setOptions().
     *
     * @throws RuntimeException
     */
    public function __construct(array $options = array())
    {
        if (!static::isAvailable()) {
            throw new RuntimeException(get_class($this) . ' is not available.');
        }

        $this->setOptions($options);
    }

    /**
     * @return array
     */
    public function getDefaultOptions()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    protected function setOptions(array $options = array())
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

    /**
     * {@inheritdoc}
     */
    public function isPersistent()
    {
        return false;
    }
}
