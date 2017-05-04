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
 * note: does not use serialize
 */
class PhpFileCache implements SimpleCacheInterface
{
    /**
     * @var string
     */
    protected $_cacheDir;

    /**
     * Default ttl is 1 day
     * @var int
     */
    protected $_ttl = 86400;

    /**
     * @var string
     */
    protected $_namespace;

    /**
     * @param array $options
     */
    public function __construct($dir, array $options = [])
    {
        $this->setCacheDir($dir);
        ClassUtil::setOptions($this, $options);
    }

    /**
     * @param $dir
     * @return $this
     * @throws Exception
     */
    public function setCacheDir($dir)
    {
        $dir = realpath("$dir");
        if (!$dir || !is_dir($dir) || !is_writable($dir)) {
            throw new Exception("Dir '$dir' not found or not writable");
        }
        $this->_cacheDir = $dir;
        return $this;
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        return $this->_cacheDir;
    }

    /**
     * @param $ns
     * @return $this
     */
    public function setNamespace($ns)
    {
        $this->_namespace = $ns;
        return $this;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }

    /**
     * @param $key
     * @return mixed
     */
    protected function _normalizeKey($key)
    {
        $key = $this->getNamespace() . $key;
        return str_replace(['.', '/', '\\'], '-', $key);
    }

    /**
     * @param $key
     * @return string
     */
    protected function _getFilename($key)
    {
        return "$this->_cacheDir/" . $this->_normalizeKey($key) . ".php";
    }

    /**
     * @param $key
     * @param null $success
     * @return null
     * @throws Exception
     */
    public function getItem($key, &$success = null)
    {
        $filename = $this->_getFilename($key);
        $now = time();

        if (file_exists($filename) && ($now - $this->_ttl) < filemtime($filename)) {
            $row = include $filename;
            if (!is_array($row)) {
                throw new Exception("Invalid cache data");
            }

            $success = true;

            return $row['data']; // note: may be null
        }

        $success = false;
        return null;
    }

    /**
     * Berie v uvahu valid_until
     * @param $key
     * @return int
     */
    public function hasItem($key)
    {
        $filename = $this->_getFilename($key);
        $now = time();

        return (
            file_exists($filename) && ($now - $this->_ttl) < filemtime($filename)
        );
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
        $filename = $this->_getFilename($key);
        $ttl = (int) ($ttl ?: $this->_ttl);

        try {

            // "row" as db analogy
            $row = ['data' => $data];
            file_put_contents(
                $filename, "<?php\nreturn " . var_export($row, true) . ";\n"
            );
            $this->touchItem($key, $ttl);

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
        $filename = $this->_getFilename($key);
        $now = time();
        $ttl = $ttl ?: $this->_ttl;

        $res = touch($filename, $now + $ttl);
        clearstatcache(false, $filename);

        return $res;
    }

    /**
     * @param $key
     * @return bool
     */
    public function removeItem($key)
    {
        $filename = $this->_getFilename($key);

        return @unlink($filename);
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

        $limit = (int) $limit;
        if ($limit < 1) {
            return false;
        }

        $affected = 0;

        foreach (glob("$this->_cacheDir/*.php", GLOB_NOSORT) as $f) {
            if (is_file($f) && $limit--) {
                unlink($f);
                $affected++;
            }
        }

        return $affected;
    }

}