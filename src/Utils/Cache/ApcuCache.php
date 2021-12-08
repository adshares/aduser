<?php

namespace App\Utils\Cache;

class ApcuCache
{
    const POLL_FREQUENCY = 0.1;

    private $namespace;

    public function __construct($namespace = '')
    {
        $this->namespace = $namespace;
    }

    public function add($key, $value, $ttl = 0): bool
    {
        return apc_add($this->namespace . '.' . $key, $value, $ttl);
    }

    public function put($key, $value, $ttl = 0)
    {
        return apc_store($this->namespace . '.' . $key, ($value), $ttl);
    }

    public function delete($key)
    {
        return apc_delete($this->namespace . '.' . $key);
    }

    public function get($key)
    {
        return apc_fetch($this->namespace . '.' . $key);
    }

    public function inc($key)
    {
        return apc_inc($this->namespace . '.' . $key);
    }

    public function dec($key)
    {
        apc_dec($this->namespace . '.' . $key);
    }

    public function putWithVersion($key, $version, $value, $ttl = 0)
    {
        $this->put($key, new CacheVersionedValue($version, $value), $ttl);
    }

    public function getWithVersion($key, $version, $exact = true)
    {
        $obj = $this->get($key);
        if (!($obj instanceof CacheVersionedValue)) {
            return false;
        }
        if ($exact && $version != $obj->version || $version > $obj->version) {
            return false;
        }
        return $obj->value;
    }

    public function getOrGenerate($key, $generate_func, $ttl = 3600, $generate_time = 0)
    {
        if ($generate_func instanceof CacheVersionedValue) {
            $value = $this->getWithVersion($key, $generate_func->version, false);
        } else {
            $value = $this->get($key);
        }
        if ($value === false) {
            $locked = false;
            if ($generate_time > 0) {
                $locked = $this->add("$key.lock", 1, $generate_time);
                $value = false;
                if (!$locked) {
                    $i = 0;
                    do {
                        $i += self::POLL_FREQUENCY;
                        usleep(self::POLL_FREQUENCY * 1000000); // 0.1s
                        if ($generate_func instanceof CacheVersionedValue) {
                            $value = $this->getWithVersion($key, $generate_func->version, false);
                        } else {
                            $value = $this->get($key);
                        }
                    } while ($i < $generate_time && $value === false); // max $generate_time
                }
            }
            if ($value === false) {
                try {
                    if ($generate_func instanceof CacheVersionedValue) {
                        $value = $generate_func->Resolve();
                        if (is_callable($ttl)) {
                            $ttl = $ttl();
                        }
                        if (is_numeric($ttl) && $ttl >= 0) {
                            $this->put($key, $generate_func, $ttl);
                        }
                    } else {
                        $value = call_user_func($generate_func);
                        if (is_callable($ttl)) {
                            $ttl = $ttl();
                        }
                        if (is_numeric($ttl) && $ttl >= 0) {
                            $this->put($key, $value, $ttl);
                        }
                    }
                } catch (\Exception $e) {
                    if ($locked) {
                        $this->delete("$key.lock");
                    }
                }
                if ($locked) {
                    $this->delete("$key.lock");
                }
            }
        }
        return $value;
    }

    public function getOrGenerateWithVersion($key, $version, $generate_func, $ttl = 21600, $generate_time = 0)
    {
        return $this->getOrGenerate($key, new CacheVersionedValue($version, $generate_func), $ttl, $generate_time);
    }
}
