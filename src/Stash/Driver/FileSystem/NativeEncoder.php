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

use Stash\Utilities;

class NativeEncoder implements EncoderInterface
{
    public function deserialize($path)
    {
        if (!file_exists($path)) {
            return false;
        }

        include($path);

        if (!isset($loaded)) {
            return false;
        }

        if (!isset($expiration)) {
            $expiration = null;
        }

        if (!isset($data)) {
            $data = null;
        }

        return array('data' => $data, 'expiration' => $expiration);
    }

    public function serialize($key, $data, $expiration = null)
    {
        $storeString = '<?php ' . PHP_EOL
            . '/* Cachekey: ' . str_replace('*/', '', $key) . ' */' . PHP_EOL
            . '/* Type: ' . gettype($data) . ' */' . PHP_EOL
            . '/* Expiration: ' . (isset($expiration) ? date(DATE_W3C, $expiration) : 'none') . ' */' . PHP_EOL
            . PHP_EOL
            . PHP_EOL
            . PHP_EOL
            . '$loaded = true;' . PHP_EOL;

        if (isset($expiration)) {
            $storeString .= '$expiration = ' . $expiration . ';' . PHP_EOL;
        }

        $storeString .= PHP_EOL;

        if (is_array($data)) {
            $storeString .= "\$data = array();" . PHP_EOL;

            foreach ($data as $key => $value) {
                $dataString = $this->encode($value);
                $keyString = "'" . str_replace("'", "\\'", $key) . "'";
                $storeString .= PHP_EOL;
                $storeString .= '/* Child Type: ' . gettype($value) . ' */' . PHP_EOL;
                $storeString .= "\$data[{$keyString}] = {$dataString};" . PHP_EOL;
            }
        } else {
            $dataString = $this->encode($data);
            $storeString .= '/* Type: ' . gettype($data) . ' */' . PHP_EOL;
            $storeString .= "\$data = {$dataString};" . PHP_EOL;
        }

        return $storeString;
    }

    public function getExtension()
    {
        return '.php';
    }

    /**
     * Finds the method of encoding that has the cheapest decode needs and encodes the data with that method.
     *
     * @param  string $data
     * @return string
     */
    protected function encode($data)
    {
        switch (Utilities::encoding($data)) {
            case 'bool':
                $dataString = (bool) $data ? 'true' : 'false';
                break;

            case 'serialize':
                $dataString = 'unserialize(base64_decode(\'' . base64_encode(serialize($data)) . '\'))';
                break;

            case 'string':
                $dataString = sprintf('"%s"', addcslashes($data, "\t\"\$\\"));
                break;

            case 'none':
            default :
                if (is_numeric($data)) {
                    $dataString = (string) $data;
                } else {
                    $dataString = 'base64_decode(\'' . base64_encode($data) . '\')';
                }
                break;
        }

        return $dataString;
    }
}
