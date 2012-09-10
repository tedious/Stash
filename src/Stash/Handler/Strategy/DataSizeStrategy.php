<?php

namespace Stash\Handler\Strategy;

use Stash\Exception\InvalidArgumentException;

/**
 * Implements an invocation strategy where each handler has a size threshold
 * set and each value is stored into the first handler for which its size is
 * less than or equal to that handler's threshold.
 */
class DataSizeStrategy implements InvocationStrategyInterface
{
    protected $ranked = array();
    protected $all;

    /**
     * Requires a single option called 'thresholds' as an array, with each
     * key being the name of a handler (equal to the key in the handler array)
     * and each value being an integer (a number in bytes) or the string 'all'.
     * Handlers with matching thresholds will be sorted in the order they
     * appear in the thresholds array; only one 'all' handler will be set.
     * 
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        if(!isset($options['thresholds']) || !is_array($options['thresholds'])) {
            throw new InvalidArgumentException('A set of thresholds must be provided.');
        }

        foreach($options['thresholds'] as $name => $threshold) {
            if(!is_numeric($threshold)) {
                if(!isset($this->all)) {
                    $this->all = $name;
                }
            } else {
                while(isset($this->ranked[$threshold])) {
                    $threshold++;
                }
                $this->ranked[$threshold] = $name;
            }
        }

        ksort($this->ranked);
    }

    /**
     * Determines the size of the data (either its string length or the length
     * of its serialization) and stores the data into the matching handler (up
     * to and including the 'all' fallback handler if no earlier handler
     * matches.) Whenever a key is stored, that key is cleared from every other
     * handler to avoid future collisions for values that change in size.
     * 
     * @param array $handlers
     * @param array $key
     * @param array $data
     * @param int $expiration
     * @return bool
     */
    public function invokeStore($handlers, $key, $data, $expiration)
    {
        $size = is_scalar($data) ? strlen($data) : strlen(serialize($data));

        $stored = false;
        foreach($this->ranked as $threshold => $name) {
            if($size > $threshold) {
                if(isset($handlers[$name])) {
                    $handlers[$name]->clear($key);
                }
                continue;
            }

            if($stored) {
                if(isset($handlers[$name])) {
                    $handlers[$name]->clear($key);
                }
            } else {
                if(isset($handlers[$name])) {
                    $stored = $handlers[$name]->storeData($key, $data, $expiration);
                }
            }
        }

        if(!$stored) {
            if(isset($handlers[$this->all])) {
                $stored = $handlers[$this->all]->storeData($key, $data, $expiration);
            }
        } else {
            if(isset($handlers[$this->all])) {
                $handlers[$this->all]->clear($key);
            }
        }

        return $stored;
    }

    /**
     * Steps through the handlers in ascending threshold order and returns
     * the first value found.
     *
     * @param array $handlers
     * @param array $key
     * @return array
     */
    public function invokeGet($handlers, $key)
    {
        foreach($handlers as $name => $handler) {
            if(!in_array($name, $this->ranked) && $name !== $this->all) {
                continue;
            }

            if($result = $handler->getData($key)) {
                return $result;
            }
        }

        return false;
    }

    /**
     * Clears the value from all handlers.
     *
     * @param array $handlers
     * @param null|array $key
     * @return bool
     */
    public function invokeClear($handlers, $key = null)
    {
        $return = true;
        foreach ($handlers as $handler) {
            $clearResults = $handler->clear($key);
            $return = $return && $clearResults;
        }

        return $return;
    }

    /**
     * Purges all handlers.
     *
     * @param array $handlers
     * @return bool
     */
    public function invokePurge($handlers)
    {
        $return = true;
        foreach ($handlers as $handler) {
            $purgeResults = $handler->purge();
            $return = $return && $purgeResults;
        }

        return $return;
    }
}