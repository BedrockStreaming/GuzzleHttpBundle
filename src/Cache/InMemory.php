<?php

declare(strict_types=1);

namespace M6Web\Bundle\GuzzleHttpBundle\Cache;

/**
 * In memory cache
 */
class InMemory implements CacheInterface
{
    protected $cache = [];
    protected $ttl = [];

    public function has($key)
    {
        if (\array_key_exists($key, $this->cache)) {
            if (\is_null($this->ttl[$key]) || $this->ttl[$key] > microtime(true)) {
                return true;
            }
            $this->remove($key);
        }

        return false;
    }

    public function get($key)
    {
        if ($this->has($key)) {
            return $this->cache[$key];
        }

        return null;
    }

    public function set($key, $value, $ttl = null)
    {
        $this->cache[$key] = $value;
        $this->ttl[$key] = \is_null($ttl) ? null : microtime(true) + $ttl;
    }

    public function remove($key)
    {
        unset(
            $this->cache[$key],
            $this->ttl[$key]
        );
    }

    public function ttl($key)
    {
        if ($this->has($key)) {
            if (!\is_null($this->ttl[$key])) {
                return (int) round($this->ttl[$key] - microtime(true));
            }

            return null;
        }

        return false;
    }
}
