<?php
define('TESTING', true);// this is basically used by the StashArray handler to decide if "isEnabled()" should return
                        // true, since the Array handler is not meant for production use, just testing. We should not
                        // use this anywhere else in the project since that would defeat the point of testing.
error_reporting(-1);

spl_autoload_register(function($class) {
    if (0 === strpos($class, 'Stash\\Test\\')) {
        $file = __DIR__ . '/../tests/' . str_replace('\\', '/', $class) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    } elseif (0 === strpos($class, 'Stash\\')) {
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', $class) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
});
