<?php

namespace Stash\Driver;

use Predis\Client;
use Stash\Utilities;

class Predis extends AbstractDriver
{
    /**
     * @var Client
     */
    private $predis;

    /**
     * The cache of indexed keys.
     *
     * @var array
     */
    private $keyCache = [];

    /**
     * Takes an array which is used to pass option values to the driver. As this is the only required function that is
     * used specifically by the developer is is where any engine specific options should go. An engine that requires
     * authentication information, as an example, should get them here.
     *
     * @param array $options
     */
    public function setOptions(array $options = [])
    {
        if (!isset($options['servers'])) {
            return;
        }

        $this->predis = new Client(
            $options['servers'],
            isset($options['clientOptions']) ? $options['clientOptions'] : null
        );
    }

    /**
     * Returns the previously stored data as well as it's expiration date in an associative array. This array contains
     * two keys- a 'data' key and an 'expiration' key. The 'data' key should be exactly the same as the value passed to
     * storeData.
     *
     * @param  array $key
     * @return array
     */
    public function getData($key)
    {
        return unserialize($this->predis->get($this->makeKeyString($key)));
    }

    /**
     * Takes in data from the exposed Stash class and stored it for later retrieval.
     *
     * *The first argument is an array which should map to a specific, unique location for that array, This location
     * should also be able to handle recursive deletes, where the removal of an item represented by an identical, but
     * truncated, key causes all of the 'children' keys to be removed.
     *
     * *The second argument is the data itself. This is an array which contains the raw storage as well as meta data
     * about the data. The meta data can be ignored or used by the driver but entire data parameter must be retrievable
     * exactly as it was placed in.
     *
     * *The third parameter is the expiration date of the item as a timestamp. This should also be stored, as it is
     * needed by the getData function.
     *
     * @param  array $key
     * @param  mixed $data
     * @param  int $expiration
     * @return bool
     */
    public function storeData($key, $data, $expiration)
    {
        $store = serialize(array('data' => $data, 'expiration' => $expiration));
        if (is_null($expiration)) {
            return 'OK' === $this->predis->set($this->makeKeyString($key), $store)->getPayload();
        } else {
            $ttl = $expiration - time();

            // Prevent us from even passing a negative ttl'd item to redis,
            // since it will just round up to zero and cache forever.
            if ($ttl < 1) {
                return true;
            }

            return 'OK' === $this->predis->setex($this->makeKeyString($key), $ttl, $store)->getPayload();
        }
    }

    /**
     * Clears the cache tree using the key array provided as the key. If called with no arguments the entire cache gets
     * cleared.
     *
     * @param  null|array $key
     * @return bool
     */
    public function clear($key = null)
    {
        if (is_null($key)) {
            $this->predis->flushDB();

            return true;
        }

        $keyString = $this->makeKeyString($key, true);
        $keyReal = $this->makeKeyString($key);
        $this->predis->incr($keyString);
        $this->predis->del($keyReal);
        $this->keyCache = [];

        return true;
    }

    /**
     * Removed any expired code from the cache. For same drivers this can just return true, as their underlying engines
     * automatically take care of time based expiration (apc or memcache for example). This function should also be used
     * for other clean up operations that the specific engine needs to handle. This function is generally called outside
     * of user requests as part of a maintenance check, so it is okay if the code in this function takes some time to
     * run,
     *
     * @return bool
     */
    public function purge()
    {
        return true;
    }

    /**
     * Returns whether the driver is able to run in the current environment or not. Any system checks - such as making
     * sure any required extensions are missing - should be done here. This is a general check; if any instance of this
     * driver can be used in the current environment it should return true.
     *
     * @return bool
     */
    public static function isAvailable()
    {
        return class_exists('Predis\Client');
    }

    protected function makeKeyString($key, $path = false)
    {
        $key = Utilities::normalizeKeys($key);

        $keyString = 'cache:::';
        $pathKey = ':pathdb::';
        foreach ($key as $name) {
            //a. cache:::name
            //b. cache:::name0:::sub
            $keyString .= $name;

            //a. :pathdb::cache:::name
            //b. :pathdb::cache:::name0:::sub
            $pathKey = ':pathdb::' . $keyString;
            $pathKey = md5($pathKey);

            if (isset($this->keyCache[$pathKey])) {
                $index = $this->keyCache[$pathKey];
            } else {
                $index = $this->predis->get($pathKey);
                $this->keyCache[$pathKey] = $index;
            }

            //a. cache:::name0:::
            //b. cache:::name0:::sub1:::
            $keyString .= '_' . $index . ':::';
        }

        return $path ? $pathKey : md5($keyString);
    }

    /**
     * {@inheritdoc}
     */
    public function isPersistent()
    {
        return true;
    }
}
