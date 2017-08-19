<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Lukas Klinzing <theluk@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Interfaces;

use IteratorAggregate;

interface CollectionInterface extends IteratorAggregate
{
    /**
     * Returns a record from cache for the specified cache item.
     * @param  ItemInterface $item
     * @return mixed
     */
    public function getRecord(ItemInterface $item);
}
