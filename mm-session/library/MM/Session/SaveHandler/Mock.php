<?php
/**
 * @author Marian Meres
 */
namespace MM\Session\SaveHandler;

use MM\Session\SaveHandlerInterface;

/**
 * FOR UNIT TESTS ONLY
 *
 * NOTE: po tom co je hotovy Session wrap (s mock modom), toto uz trosku
 * straca zmysel...
 *
 */
class Mock implements SaveHandlerInterface
{
    /**
     *
     */
    public static $data = array();

    /**
     *
     */
    protected $_lifetime;

    /**
     *
     */
    public static function reset()
    {
        self::$data = array();
    }

    /**
     * @param $savePath
     * @param $sessName
     * @return bool
     */
    public function open($savePath, $sessName)
    {
        return true;
    }

    /**
     * @param string $sessId
     * @return string
     */
    public function read($sessId)
    {
        if (isset(self::$data[$sessId])) {
            $row = self::$data[$sessId];
            if ($row['modified'] + $row['lifetime'] > time()) {
                return $row['data'];
            }
            $this->destroy($sessId);
        }
        return '';
    }

    /**
     * @param string $sessId
     * @param string $data
     * @return bool
     */
    public function write($sessId, $data)
    {
        $row = array(
            'modified' => time(), 'data' => $data,
        );

        if (isset(self::$data[$sessId])) {
            $row['lifetime'] = null !== $this->_lifetime
                ? $this->_lifetime : self::$data[$sessId]['lifetime'];
        } else {
            $row['lifetime'] = null !== $this->_lifetime
                ? $this->_lifetime : ini_get("session.gc_maxlifetime");
        }

        self::$data[$sessId] = $row;
        return true;
    }

    /**
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * @param string $sessId
     * @return bool
     */
    public function destroy($sessId)
    {
        unset(self::$data[$sessId]);
        return true;
    }

    /**
     * @param int $lifetime
     * @return bool|mixed
     */
    public function gc($lifetime)
    {
        foreach (self::$data as $sid => $row) {
            if ($row['modified'] + $row['lifetime'] <= time()) {
                unset(self::$data[$sid]);
            }
        }
        return true;
    }

    /**
     * @param bool $registerShutdown
     * @return $this|mixed
     */
    public function registerSaveHandler($registerShutdown = true)
    {
        session_set_save_handler(
            array($this, "open"),
            array($this, "close"),
            array($this, "read"),
            array($this, "write"),
            array($this, "destroy"),
            array($this, "gc")
        );
        if ($registerShutdown) {
            register_shutdown_function('session_write_close');
        }
        return $this;
    }

    /**
     * @param $ttl
     * @return $this|mixed
     */
    public function setLifetime($ttl)
    {
        $this->_lifetime = null === $ttl ? null : (int) $ttl;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLifetime()
    {
        return $this->_lifetime;
    }
}