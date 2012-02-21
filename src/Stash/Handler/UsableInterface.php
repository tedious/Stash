<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Handler;

/**
 * Interface for Handlers and Sub-Handlers to allow for checking if enabled in current environment
 *
 * @package Stash
 * @author  Kevin Bond <kevinbond@gmail.com>
 */
interface UsableInterface
{
    /**
     * Returns whether the handler is able to run in the current environment or not. Any system checks - such as making
     * sure any required extensions are missing - should be done here.
     *
     * @return bool
     */
    public function canEnable();
}
