<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Interfaces;

interface ItemInterface
{

    public function disable();

    public function getKey();

    public function clear();

    public function get($invalidation = 0, $arg = null, $arg2 = null);

    public function isMiss();

    public function lock($ttl = null);

    public function set($data, $ttl = null);

    public function extend($ttl = null);

    public function isDisabled();

    public function setLogger($logger);
}
