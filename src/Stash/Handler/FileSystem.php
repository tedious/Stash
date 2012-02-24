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

use Stash;
use Stash\Exception\LogicException;

/**
 * StashFileSystem stores cache objects in the filesystem as native php, making the process of retrieving stored data
 * as performance intensive as including a file. Since the data is stored as php this module can see performance
 * benefits from php opcode caches like APC and xcache.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class FileSystem implements HandlerInterface
{
    /**
     * This is the path to the file which will be used to store the cached item. It is based off of the key.
     *
     * @var string
     */
    protected $path;

    /**
     * This is the array passed from the main Cache class, which needs to be saved
     *
     * @var array
     */
    protected $data;

    /**
     * This flag is used to disable the cacheHandler for this one instance.
     *
     * @var bool
     */
    protected $cache_enabled = true;

    /**
     * This function stores the path information generated by the makePath function so that it does not have to be
     * calculated each time the handler is called. This only stores path information, it does not store the data to be
     * cached
     *
     * @var array
     */
    protected $memStore = array();

    protected $memStoreLimit;

    /**
     * This is the base path for the cache items to be saved in. This defaults to a directory in the tmp directory (as
     * defined by the configuration) called 'stash_', which it will create if needed.
     *
     * @var string
     */
    protected $cachePath;

    protected $filePermissions;
    protected $dirPermissions;
    protected $directorySplit;

    protected $defaultOptions = array('filePermissions' => 0660,
                                      'dirPermissions' => 0770,
                                      'dirSplit' => 2,
                                      'memKeyLimit' => 20
    );

    public function __construct(array $options = array())
    {
        $options = array_merge($this->defaultOptions, $options);

        $this->cachePath = isset($options['path']) ? $options['path'] : \Stash\Utilities::getBaseDirectory($this);
        $this->cachePath = rtrim($this->cachePath, '\\/') . '/';

        $this->filePermissions = $options['filePermissions'];
        $this->dirPermissions = $options['dirPermissions'];

        if (!is_numeric($options['dirSplit']) || $options['dirSplit'] < 1) {
            $options['dirSplit'] = 1;
        }

        $this->directorySplit = (int)$options['dirSplit'];

        if (!is_numeric($options['memKeyLimit']) || $options['memKeyLimit'] < 1) {
            $options['memKeyLimit'] = 0;
        }

        $this->memStoreLimit = (int)$options['memKeyLimit'];
    }

    /**
     * Empty destructor to maintain a standardized interface across all handlers.
     *
     */
    public function __destruct()
    {
    }

    protected function makeKeyString($key)
    {
        $keyString = '';
        foreach ($key as $group) {
            $keyString .= $group . '/';
        }
        return $keyString;
    }

    /**
     * This function retrieves the data from the file. If the file doesn't exist, or is currently being written to, it
     * will return false. If the file is already being written to, this instance of the handler gets disabled so as not
     * to have a bunch of writes get queued up when a cache item fails to hit.
     *
     * @return bool
     */
    public function getData($key)
    {
        return self::getDataFromFile($this->makePath($key));
    }

    static protected function getDataFromFile($path)
    {
        if (!file_exists($path)) {
            return false;
        }

        include($path);
        return !isset($data) && !@is_null($data) ? false : array('data' => $data, 'expiration' => $expiration);
    }


    /**
     * This function takes the data and stores it to the path specified. If the directory leading up to the path does
     * not exist, it creates it.
     *
     * @param array $key
     * @param array $data
     * @param int $expiration
     * @return bool
     */
    public function storeData($key, $data, $expiration)
    {
        if (!$this->cache_enabled) {
            return false;
        }

        $path = $this->makePath($key);

        if (!file_exists($path)) {
            if (!is_dir(dirname($path))) {
                if (!mkdir(dirname($path), $this->dirPermissions, true)) {
                    return false;
                }
            }

            if (!(touch($path) && chmod($path, $this->filePermissions))) {
                return false;
            }
        }

        $storeString = '<?php ' . PHP_EOL . '/* Cachekey: ' . $this->makeKeyString($key) . ' */' . PHP_EOL . '/* Type: ' . gettype($data) . ' */' . PHP_EOL . '$expiration = ' . $expiration . ';' . PHP_EOL;

        if (is_array($data)) {
            $storeString .= "\$data = array();" . PHP_EOL;

            foreach ($data as $key => $value) {
                $dataString = $this->encode($value);
                $storeString .= PHP_EOL;
                $storeString .= '/* Child Type: ' . gettype($value) . ' */' . PHP_EOL;
                $storeString .= "\$data['{$key}'] = {$dataString};" . PHP_EOL;
            }
        } else {

            $dataString = $this->encode($data);
            $storeString .= '/* Type: ' . gettype($data) . ' */' . PHP_EOL;
            $storeString .= "\$data = {$dataString};" . PHP_EOL;
        }

        return false !== file_put_contents($path, $storeString, LOCK_EX);
    }

    protected function encode($data)
    {
        switch (\Stash\Utilities::encoding($data)) {
            case 'bool':
                $dataString = (bool)$data ? 'true' : 'false';
                break;

            case 'serialize':
                $dataString = 'unserialize(base64_decode(\'' . base64_encode(serialize($data)) . '\'))';
                break;

            case 'string':
                $dataString = sprintf('"%s"', addcslashes($data, "\t\"\$\\"));

            case 'none':
            default :
                if (is_numeric($data)) {
                    $dataString = (string)$data;
                } else {
                    $dataString = 'base64_decode(\'' . base64_encode($data) . '\')';
                }
                break;
        }
        return $dataString;
    }

    /**
     * This function takes in an array of strings (the key) and uses them to create a path to save the cache item to.
     * It starts with the cachePath (or a new 'cache' directory in the config temp directory) and then uses each element
     * of the array as a directory (after putting the element through md5(), which was the most efficient way to make
     * sure it was filesystem safe). The last element of the array gets a php extension attached to it.
     *
     * @param array $key Null arguments return the base directory.
     * @return string
     */
    protected function makePath($key = null)
    {
        if (!isset($this->cachePath)) {
            throw new LogicException('Unable to load system without a base path.');
        }

        $basePath = $this->cachePath;

        if (count($key) == 0) {
            return $basePath;
        }

        // When I profiled this compared to the "implode" function, this was much faster. This is probably due to the
        // small size of the arrays and the overhead from function calls. This may seem like a ridiculous
        // micro-optimization, but I only did it after profiling the code with xdebug and noticing a legitimate
        // difference, most likely due to the number of times this function can get called in a scripts.
        // Please don't look at me like that.
        $memkey = '';
        foreach ($key as $group) {
            $memkey .= $group . '/';
        }

        if (isset($this->memStore['keys'][$memkey])) {
            return $this->memStore['keys'][$memkey];
        } else {
            $pathPieces = array();
            $path = $basePath;
            $len = floor(32 / $this->directorySplit);
            $key = \Stash\Utilities::normalizeKeys($key);

            foreach ($key as $index => $value) {
                if (strpos($value, '@') === 0) {
                    $path .= substr($value, 1) . '/';
                    continue;
                }

                $sLen = strlen($value);
                $len = floor($sLen / $this->directorySplit);
                for ($i = 0; $i < $this->directorySplit; $i++) {
                    $start = $len * $i;
                    if ($i == $this->directorySplit) {
                        $len = $sLen - $start;
                    }
                    $path .= substr($value, $start, $len) . '/';
                }
            }

            $path = rtrim($path, '/') . '.php';
            $this->memStore['keys'][$memkey] = $path;

            // in most cases the key will be used almost immediately or not at all, so it doesn't need to grow too large
            if (count($this->memStore['keys']) > $this->memStoreLimit) {
                foreach (array_rand($this->memStore['keys'], ceil($this->memStoreLimit / 2) + 1) as $empty) {
                    unset($this->memStore['keys'][$empty]);
                }
            }

            return $path;
        }
    }

    /**
     * This function clears the data from a key. If a key points to both a directory and a file, both are erased. If
     * passed null, the entire cache directory is removed.
     *
     * @param null|array $key
     * @return bool
     */
    public function clear($key = null)
    {
        $path = $this->makePath($key);
        if (is_file($path)) {
            $return = true;
            unlink($path);
        }

        if (strpos($path, '.php') !== false) {
            $path = substr($path, 0, -4);
        }

        if (is_dir($path)) {
            return \Stash\Utilities::deleteRecursive($path);
        }

        return isset($return);
    }

    /**
     * Cleans out the cache directory by removing all stale cache files and empty directories.
     *
     * @return bool
     */
    public function purge()
    {
        $startTime = time();
        $filePath = $this->makePath();

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

            if (!file_exists($filename)) {
                continue;
            }

            $data = self::getDataFromFile($filename);
            if ($data['expiration'] <= $startTime) {
                unlink($filename);
            }

        }
        unset($directoryIt);
        return true;
    }

    /**
     * This function checks to see if it is possible to enable this handler. This returns true no matter what, since
     * this is the handler of last resort.
     *
     * @return bool true
     */
    public function canEnable()
    {
        return true;
    }

}
