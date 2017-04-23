<?php
/**
 * @author: mmeres
 */

namespace MM\SimpleCache;

use MM\Util\DbUtilPdo;
use MM\Util\ClassUtil;

/**
 * Simple key value db caching utitlity. May not be suitable for large scale.
 *
 * Note: api follows zend2 cache api
 */
class DbCache implements SimpleCacheInterface
{
    /**
     * @var string
     */
    protected $_tableName = '_simple_cache';

    /**
     * @var DbUtilPdo
     */
    protected $_db;

    /**
     * Default ttl is 1 day
     * @var int
     */
    protected $_ttl = 86400;

    /**
     * custom serializer if provided
     * @var \Closure
     */
    protected $_serialize;

    /**
     * custom unserialized if provided
     * @var \Closure
     */
    protected $_unserialize;

    /**
     * @param array $options
     */
    public function __construct(DbUtilPdo $db, array $options = [])
    {
        $this->_db = $db;
        ClassUtil::setOptions($this, $options);
    }

    /**
     * @param \Closure $ser
     * @return $this
     */
    public function setSerialize(\Closure $ser)
    {
        $this->_serialize = $ser;
        return $this;
    }

    /**
     * @param \Closure $unser
     * @return $this
     */
    public function setUnserialize(\Closure $unser)
    {
        $this->_unserialize = $unser;
        return $this;
    }

    /**
     * @param $tableName
     * @return $this
     */
    public function setTableName($tableName)
    {
        $this->_tableName = "$tableName";
        return $this;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->_tableName;
    }

    /**
     * @param $key
     * @param null $success
     * @return null
     */
    public function getItem($key, &$success = null)
    {
        // intentionally fetching 2 fields, so we can easily distinguish between
        // null and not found
        $row = $this->_db->fetchRow('id,data', $this->_tableName, [
            'id' => $key, 'valid_until>=' => time()
        ]);

        if (!$row) {
            $success = false;
            return null;
        }

        $success = true;

        // pozor, moze vratit aj null
        $data = $row['data'];

        if ($this->_unserialize) {
            $s = $this->_unserialize;
            $data = $s($data);
        } else {
            $data = unserialize($data);
        }

        return $data;
    }

    /**
     * Berie v uvahu valid_until
     * @param $key
     * @return int
     */
    public function hasItem($key)
    {
        return (bool) $this->_db->fetchCount($this->_tableName, [
            'id' => $key, 'valid_until>=' => time()
        ]);
    }

    /**
     * @param $key
     * @param $data
     * @param null $ttl
     * @param bool $throwOnFailure
     * @return bool
     * @throws \Exception
     */
    public function setItem($key, $data, $ttl = null, $throwOnFailure = true)
    {
        $now = time();
        $ttl = (int) ($ttl ?: $this->_ttl);

        if ($this->_serialize) {
            $s = $this->_serialize;
            $data = $s($data);
        } else {
            $data = serialize($data);
        }

        try {

            if ($this->_db->fetchCount($this->_tableName, ['id' => $key])) {

                $this->_db->update(
                    $this->_tableName,
                    ['data' => $data, 'valid_until' => $now + $ttl],
                    ['id' => $key]
                );

            } else {

                // note: race condition potential
                $this->_db->insert($this->_tableName, [
                    'id' => $key,
                    'data' => $data,
                    'valid_until' => $now + $ttl
                ]);
            }

        } catch(\Exception $e) {
            if ($throwOnFailure) {
                throw $e;
            }
            return false;
        }

        return true;
    }

    /**
     * @param $key
     * @param null $ttl
     * @return bool
     */
    public function touchItem($key, $ttl = null)
    {
        $now = time();
        $ttl = $ttl ?: $this->_ttl;

        return (bool) $this->_db->update(
            $this->_tableName,
            ['valid_until' => $now + (int) $ttl],
            ['id' => $key]
        );
    }

    /**
     * @param $key
     * @return bool
     */
    public function removeItem($key)
    {
        return (bool) $this->_db->delete($this->_tableName, ['id' => $key]);
    }

    /**
     * @param int $probability
     * @param int $divisor
     * @param int $limit
     * @return bool|int|string|void
     */
    public function garbageCollect($probability = 1, $divisor = 1000, $limit = 1000)
    {
        // return early if nothing to do
        if (0 === ($probability = round(abs($probability)))) {
            return false;
        }

        $divisor = min(mt_getrandmax(), max(1, abs($divisor)));
        $divisor = round($divisor / $probability);

        if (1 !== mt_rand(1, $divisor)) {
            return false;
        }

        $addons = [];

        // delete limit is only supported on mysql
        if ($this->_db->isMysql()) {
            $addons['limit'] = (int) $limit;
        }

        $affected = $this->_db->delete(
            $this->getTableName(), ['valid_until<' => time()], $addons
        );

        return $affected;
    }

    /**
     * Na ilustraciu
     * @return string
     */
    public static function getSampleSchema()
    {
        $sql = <<<EOS
drop table if exists _simple_cache;
create table _simple_cache (
    id          varchar(255) primary key,
    data        text,
    -- valid_until = now + ttl
    valid_until int {unsigned} not null
);
create index _simple_cache_valid_until on _simple_cache (valid_until);
EOS;
        return $sql;
    }

}