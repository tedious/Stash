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

namespace Stash;

/**
 * The Autoloader loads classes from the Stash library.
 *
 * @package Stash
 * @author Robert Hafner <tedivm@tedivm.com>
 */
class Autoloader
{
    /**
     * @var string Base path the autoloader uses when loading classes.
     */
    static protected $path;

    /**
     * Registers the autoloader using the spl_autoload system.
     */
    static public function register()
    {
        ini_set('unserialize_callback_func', 'spl_autoload_call');
        spl_autoload_register(array(new self, 'autoload'));
    }

    /**
     * Initializes the autoloader (at this point this just means setting the base path).
     */
    static public function init()
    {
        self::$path = dirname(__file__) . '/';
    }

    /**
     * Takes a class name and attempts to load it into the current environment. If the class is not part of the Stash
     * project, or the file is unable to be opened, this function returns false.
     *
     * @param string $classname
     * @return bool Returns true when class is successfully loaded.
     */
    static function autoload($classname)
    {

        if (class_exists($classname, false)) {
            return true;
        }

        $classname = ltrim($classname);

        if (strpos($classname, 'Stash') !== 0) {
            return false;
        }

        // Everything has at least one namespace (Stash)
        if (strpos($classname, '\\', 1) === false) {
            return false;
        }

        // Remove "Stash", since this is in relation to this folder
        $filebase = substr($classname, 6);
        $fileName = self::$path . str_replace('\\', DIRECTORY_SEPARATOR, $filebase) . '.php';

        if (!file_exists($fileName)) {
            return false;
        }

        include($fileName);
        return class_exists($classname, false) || interface_exists($classname, false);
    }
}

/**
 * This call makes sure the path is set when the class is first loaded.
 */
Autoloader::init();
