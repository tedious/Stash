<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Exception;

use \Psr\Cache\CacheException;

/**
 * Interface for the Stash exceptions.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
interface Exception extends CacheException
{
}
