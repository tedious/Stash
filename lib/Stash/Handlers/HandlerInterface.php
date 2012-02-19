<?php
/**
 * Stash
 *
 * Copyright (c) 2009-2011, Robert Hafner <tedivm@tedivm.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Robert Hafner nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Stash
 * @author     Robert Hafner <tedivm@tedivm.com>
 * @copyright  2009-2011 Robert Hafner <tedivm@tedivm.com>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://code.google.com/p/stash/
 * @since      File available since Release 0.9.1
 * @version    Release: 0.9.5
 */

namespace Stash\Handlers;

/**
 * Stash Handlers are the engines behind the Stash library. These classes handle the low level operations- retrieving,
 * storing and deleting items in the persistant cache pool. By creating new handlers developers can add new caching
 * methods to their applications with extremely minmal changes to their existing code base. This interface defines the
 * standard for those handlers and all of them are required to implement it. When writing new cache storage engines this
 * is the place to start.
 *
 * A few important notes when implementing this interface-
 *
 * * Unlike with the Stash class itself, instances of handlers are meant to be reused over and over again, allowing them
 * to avoid the overhead of repeadtedly opening and closing resources. There are times when multiple instances of an
 * engine will be created though (where a developer wants two separate cache pools for example) so static caching
 * techniques should be avoided- the StashHandler will be kept open and reused by the developers, so instance properties
 * will persist in a useful way.
 *
 * * Each storage engine must be able to handle multiple requests with the same object, meaning functions like
 * getData can be called multiple times in sequence while storeData may be mixed in at random requences, all using
 * different keys.
 *
 * * Keys are passed as arrays that represent a hierarchal 'location' where the cached data is virtually stored, with
 *  each item in the array being a deeper level of that hierarchy. In other words $key[0] is the root of the cache tree
 *  and $key[2] is the child of $key[0] + $key[1]. Each level can be both a piece of data and a parent location, which
 *  is particularly important for purge and delete operations.
 *
 *
 * @package Stash
 * @author Robert Hafner <tedivm@tedivm.com>
 */

interface HandlerInterface
{
    /**
     * Takes an array which is used to pass option values to the handler. As this is the only required function that is
     * used specifically by the developer is is where any engine specific options should go. An engine that requires
     * authentication information, as an example, should get them here.
     *
     * @param array $options
     */
    public function __construct($options = array());

    /**
     * Returns the previously stored data as well as it's expiration date in an associative array. This array contains
     * two keys- a 'data' key and an 'expiration' key. The 'data' key should be exactly the same as the value passed to
     * storeData.
     *
     * @return array
     */
    public function getData($key);

    /**
     * Takes in data from the exposed Stash class and stored it for later retrieval.
     *
     * *The first argument is an array which should map to a specific, unique location for that array, This location
     * should also be able to handle recursive deletes, where the removal of an item represented by an identicle, but
     * truncated, key causes all of the 'children' keys to be removed.
     *
     * *The second argument is the data itself. This is an array which contains the raw storage as well as meta data
     * about the data. The meta data can be ignored or used by the handler but entire data parameter must be retrievable
     * exactly as it was placed in.
     *
     * *The third parameter is the expiration date of the item as a timestamp. This should also be stored, as it is
     * needed by the getData function.
     *
     * @param string $key
     * @param array $data
     * @param int $expiration
     * @return bool
     */
    public function storeData($key, $data, $expiration);

    /**
     * Clears the cache tree using the key array provided as the key. If called with no arguments the entire cache gets
     * cleared.
     *
     * @param null|array $key
     * @return bool
     */
    public function clear($key = null);

    /**
     * Removed any expired code from the cache. For same handlers this can just return true, as their underlying engines
     * automatically take care of time based expiration (apc or memcache for example). This function should also be used
     * for other clean up operations that the specific engine needs to handle. This function is generally called outside
     * of user requests as part of a maintenance check, so it is okay if the code in this function takes some time to
     * run,
     *
     * @return bool
     */
    public function purge();

    /**
     * Returns whether the handler is able to run in the current environment or not. Any system checks- such as making
     * sure any required extensions are missing- should be done here.
     *
     * @return bool
     */
    static function canEnable();
}
