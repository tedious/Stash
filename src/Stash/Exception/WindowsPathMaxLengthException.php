<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jonathan
 * Date: 7/25/13
 * Time: 11:49 AM
 * To change this template use File | Settings | File Templates.
 */

namespace Stash\Exception;


/**
 * Thrown when path exceeds 260 total Windows PHP character limit.
 *
 * Solutions:
 *   1. Move the cache path to a root directory to reduce path length.
 *   2. Use a different Stash driver, such as SQLite.
 *   3. Use less subkeys to reduce the depth of the cache path.
 *   4. Switch to a shorter hashing algorithm such as crc32 (not recommended as could produce collisions)
 *
 * Reasons we can't fix this:
 *   * PHP currently does not and will not support Windows extended length paths (\\?\C:\...)
 *     http://www.mail-archive.com/internals@lists.php.net/msg62672.html
 *
 * Class WindowsPathMaxLengthException
 * @package Stash\Exception
 */
class WindowsPathMaxLengthException extends \Exception implements Exception {
    public function __construct($message="",$code=0,$previous=null) {
        parent::__construct("Cache path exceeds Windows PHP MAX_LENGTH of 260 characters. " . $message,$code,$previous);
    }
}