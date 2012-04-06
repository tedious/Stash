<?php

namespace Stash\Handler\Strategy;

use Stash\Exception\InvalidArgumentException;

class DataSizeStrategy implements InvocationStrategyInterface
{
    protected $ranked = array();
    protected $all;

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

    public function invokeStore($handlers, $key, $data, $expiration)
    {
        $size = is_scalar($data['return']) ? strlen($data['return']) : strlen(serialize($data['return']));

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
                $stored =$handlers[$this->all]->storeData($key, $data, $expiration);
            }
        } else {
            if(isset($handlers[$this->all])) {
                $handlers[$this->all]->clear($key);
            }
        }

        return $stored;
    }

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

    public function invokeClear($handlers, $key = null)
    {
        $return = true;
        foreach ($handlers as $handler) {
            $clearResults = $handler->clear($key);
            $return = $return && $clearResults;
        }

        return $return;
    }

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