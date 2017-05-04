<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 03/09/15
 * Time: 09:23
 */

namespace MM\SimpleCache;

/**
 * Interface CacheInterface
 * @package MM\Util\SimpleCache
 */
interface SimpleCacheInterface
{
    /**
     * @param $ns
     * @return mixed
     */
    public function setNamespace($ns);

    /**
     * @return mixed
     */
    public function getNamespace();

    /**
     * @param $key
     * @param null $success
     * @return mixed
     */
    public function getItem($key, &$success = null);

    /**
     * @param $key
     * @return bool
     */
    public function hasItem($key);

    /**
     * @param $key
     * @param $data
     * @param null $ttl
     * @param bool|true $throwOnFailure
     * @return bool
     */
    public function setItem($key, $data, $ttl = null, $throwOnFailure = true);

    /**
     * @param $key
     * @param null $ttl
     * @return bool
     */
    public function touchItem($key, $ttl = null);

    /**
     * @param $key
     * @return bool
     */
    public function removeItem($key);

    /**
     * @param int $probability
     * @param int $divisor
     * @param int $limit
     * @return bool|int
     */
    public function garbageCollect($probability = 1, $divisor = 1000, $limit = 1000);
}