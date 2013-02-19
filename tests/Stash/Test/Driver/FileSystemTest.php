<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test\Driver;

use Stash\Driver\FileSystem;
use Stash\Item;

function strdup($str)
{
    return $str;
}

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class FileSystemTest extends AbstractDriverTest
{
    protected $driverClass = 'Stash\Driver\FileSystem';

    protected function getOptions()
    {
        return array('memKeyLimit' => 2);
    }
    
    /**
     * @expectedException Stash\Exception\RuntimeException
     */
    public function testOptionKeyHashFunctionException()
    {
        $driver = new FileSystem(array('keyHashFunction' => 'foobar_'.mt_rand()));
    }
    
    public function testOptionKeyHashFunction()
    {
        $driver = new FileSystem(array('keyHashFunction' => 'md5'));
    }
    
    /**
     * Test that the paths are created using the key hash function.
     */
    public function testOptionKeyHashFunctionDirs()
    {
        $hashfunctions = array('Stash\Test\Driver\strdup', 'strrev', 'md5');
        $paths = array('one', 'two', 'three', 'four');
        
        
        foreach($hashfunctions as $hashfunction) {
            
            $driver = new FileSystem(array(
                'keyHashFunction' => $hashfunction,
                'path' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'stash',
                'dirSplit' => 1
            ));
            
            $rand = str_repeat(uniqid(), 32);
            
            $item = new Item($driver, $paths);
            $item->set($rand);
            
            $allpaths = array_merge(array('cache'), $paths);
            $predicted = sys_get_temp_dir().
                            DIRECTORY_SEPARATOR.
                            'stash'.
                            DIRECTORY_SEPARATOR.
                            implode(DIRECTORY_SEPARATOR, 
                                array_map($hashfunction, $allpaths)).
                            '.php';
            
            $this->assertFileExists($predicted);
        }
    }
}
