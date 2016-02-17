<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Driver\FileSystem;

class SerializerEncoder implements EncoderInterface
{
    public function deserialize($path)
    {
        if (!file_exists($path)) {
            return false;
        }

        $raw = unserialize(file_get_contents($path));
        if (is_null($raw) || !is_array($raw)) {
            return false;
        }

        $data = $raw['data'];
        $expiration = isset($raw['expiration']) ? $raw['expiration'] : null;

        return array('data' => $data, 'expiration' => $expiration);
    }

    public function serialize($key, $data, $expiration = null)
    {
        $processed = serialize(array(
            'key' => $key,
            'data' => $data,
            'expiration' => $expiration
        ));

        return $processed;
    }

    public function getExtension()
    {
        return '.pser';
    }
}
