<?php
define('TESTING', true); // this is basically used by the StashArray handler to decide if "isEnabled()" should return
						// true, since the Array handler is not meant for production use, just testing. We should not
						// use this anywhere else in the project since that would defeat the point of testing.
error_reporting(-1);
$currentDir = dirname(__FILE__) . '/';
include($currentDir . '../lib/Stash/Autoloader.class.php');
StashAutoloader::register();
?>