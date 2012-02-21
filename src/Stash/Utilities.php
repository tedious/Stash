<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash;

use Stash\Handler\HandlerInterface;
use Stash\Exception\Exception;

/**
 * StashUtilities contains static functions used throughout the Stash project, both by core classes and handlers.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Utilities
{
    /**
     * Various handlers use this to define what kind of encoding to use on objects being cached. It needs to be revamped
     * a bit.
     */
    static function encoding($data)
    {
        if (is_scalar($data)) {
            if (is_bool($data)) {
                return 'bool';
            }

            if (is_numeric($data) && ($data >= 2147483648 || $data < -2147483648)) {
                return 'serialize';
            }

            if (is_string($data)) {
                return 'string';
            }

            return 'none';
        }

        return 'serialize';
    }

    /**
     * Uses the encoding function to define an encoding and uses it on the data. This system is going to be revamped.
     */
    static function encode($data)
    {
        switch (self::encoding($data)) {
            case 'bool':
                return $data ? 'true' : 'false';

            case 'serialize':
                return serialize($data);

            case 'string':
            case 'none':
            default:
        }
        return $data;
    }

    /**
     * Takes a piece of data encoded with the 'encode' function and returns it's actual value.
     *
     */
    static function decode($data, $method)
    {
        switch ($method) {
            case 'bool':
                // note that true is a string, so this
                return $data == 'true' ? true : false;

            case 'serialize':
                return unserialize($data);

            case 'string':
            case 'none':
            default:
        }
        return $data;
    }

    /**
     * Used to get around late static binding issues and other fun things in versions of php prior to 5.3. It
     * unfortunately has some perfomance issues and is one of the reasons why support for earlier versions of php will
     * be dropped.
     *
     * @param string $className
     * @param string $functionName
     * @param mixed $arguments,...
     */
    static function staticFunctionHack($className, $functionName)
    {
        $arguments = func_get_args();
        $className = array_shift($arguments);
        $functionName = array_shift($arguments);

        if (is_object($className)) {
            $className = get_class($className);
        }

        /* This dirty hack is brought to you by php < 5.3 failing at oop */
        if (is_callable(array($className, $functionName))) {
            return call_user_func_array(array($className, $functionName), $arguments);
        } else {
            throw new Exception('static function ' . $functionName . ' not found in class ' . $className);
        }
    }

    /**
     * Returns the default base directory for the system when one isn't provided by the developer. This is a directory
     * of last resort and can cause problems if one library is shared by multiple projects. The directory returned
     * resides in the system's temap folder and is specific to each Stash installation and handler.
     *
     * @param HandlerInterface $handler
     * @return string Path for Stash files
     */
    static function getBaseDirectory(HandlerInterface $handler = null)
    {
        $tmp = sys_get_temp_dir();
        $lastChar = substr($tmp, -1, 1);
        if ($lastChar !== '\\' && $lastChar !== '/') {
            $tmp .= DIRECTORY_SEPARATOR;
        }

        $baseDir = $tmp . 'stash' . DIRECTORY_SEPARATOR . md5(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
        if (isset($handler)) {
            $baseDir .= str_replace(array('/', '\\'), '_', get_class($handler)) . '/';
        }

        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0770, true);
        }

        return $baseDir;
    }

    /**
     * Deletes a directory and all of its contents.
     *
     * @param string $file Path to file or directory.
     * @return bool Returns true on success, false otherwise.
     */
    static function deleteRecursive($file)
    {
        if (substr($file, 0, 1) !== '/' && substr($file, 1, 2) !== ':\\') {
            throw new Exception('deleteRecursive function requires an absolute path.');
        }

        $badCalls = array('/', '/*', '/.', '/..');
        if (in_array($file, $badCalls)) {
            throw new Exception('deleteRecursive function does not like that call.');
        }

        $filePath = rtrim($file, ' /');

        if (is_dir($filePath)) {
            $directoryIt = new \RecursiveDirectoryIterator($filePath);

            foreach (new \RecursiveIteratorIterator($directoryIt, \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                $filename = $file->getPathname();
                if ($file->isDir()) {
                    $dirFiles = scandir($file->getPathname());
                    if ($dirFiles && count($dirFiles) == 2) {
                        $filename = rtrim($filename, '/.');
                        rmdir($filename);
                    }
                    unset($dirFiles);
                    continue;
                }


                if (file_exists($filename)) {
                    unlink($filename);
                }

            }
            unset($directoryIt);

            if (is_dir($filePath)) {
                rmdir($filePath);
            }

            return true;
        } elseif (is_file($filePath)) {
            return unlink($file);
        }

        return false;
    }

    static function normalizeKeys($keys, $hash = 'md5')
    {
        $pKey = array();
        foreach ($keys as $keyPiece) {
            $prefix = substr($keyPiece, 0, 1) == '@' ? '@' : '';
            //$pKeyPiece = $prefix . dechex(crc32($keyPiece));
            $pKeyPiece = $prefix . $hash($keyPiece);
            $pKey[] = $pKeyPiece;
        }
        return $pKey;
    }
}
