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
	public function __construct(array $options = array()) 
	{
		// empty
	}

    
	/* 
	 * (non-PHPdoc)
     * @see \Stash\Driver\DriverInterface::clear()
     */
    public function clear($key = null)
    {
        return true;
    }

	/* 
	 * (non-PHPdoc)
     * @see \Stash\Driver\DriverInterface::getData()
     */
    public function getData($key)
    {
        return false;
	}

	/* 
	 * (non-PHPdoc)
     * @see \Stash\Driver\DriverInterface::purge()
     */
    public function purge()
    {
		return true;
	}

	/* 
	 * (non-PHPdoc)
     * @see \Stash\Driver\DriverInterface::storeData()
     */
    public function storeData($key, $data, $expiration)
    {
		return true;
    }
    
    /* 
     * (non-PHPdoc)
     * @see \Stash\Driver\DriverInterface::isAvailable()
     */
    public static function isAvailable()
    {
        return true;
    }
   
}