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
 * This class provides a NULL caching handler, it always takes values, but never saves them
 * Can be used as an default save handler
 * 
 * @author Benjamin Zikarsky <benjamin.zikarsky@perbility.de>
 */
class BlackHole implements HandlerInterface
{
    
	/**
	 * NOOP constructor
	 */
	public function __construct(array $options = array()) 
	{
		// empty
	}

    
	/* 
	 * (non-PHPdoc)
     * @see \Stash\Handler\HandlerInterface::clear()
     */
    public function clear($key = null)
    {
        return true;
    }

	/* 
	 * (non-PHPdoc)
     * @see \Stash\Handler\HandlerInterface::getData()
     */
    public function getData($key)
    {
        return false;
	}

	/* 
	 * (non-PHPdoc)
     * @see \Stash\Handler\HandlerInterface::purge()
     */
    public function purge()
    {
		return true;
	}

	/* 
	 * (non-PHPdoc)
     * @see \Stash\Handler\HandlerInterface::storeData()
     */
    public function storeData($key, $data, $expiration)
    {
		return true;
    }
    
    /* 
     * (non-PHPdoc)
     * @see \Stash\Handler\HandlerInterface::isAvailable()
     */
    public static function isAvailable()
    {
        return true;
    }
   
}