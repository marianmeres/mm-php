<?php
/**
 * @author Marian Meres
 */
namespace MM\Session\SaveHandler;

use MM\Session\SaveHandlerInterface;
use MM\Session\Exception;

/**
 * Toto by sa malo chovat ako nativna "files" saveHandler implementacia, pre
 * hardcore produkciu vsak skor nepouzivat (i ked nema tu moc co nefungovat...)
 */
class FlatFile implements SaveHandlerInterface
{
    /**
     * @var string
     */
    protected $_savePath;

    /**
     * @var string
     */
    protected $_name;

    /**
     * @var string
     */
    public $prefix = 'sess_';

    /**
     * @param array $options
     * @throws \MM\Session\Exception
     */
    public function __construct(array $options = array())
    {
        if (isset($options['dir'])) {
            if (!is_dir($options['dir'])) {
                throw new Exception("Invalid dir '$options[dir]'");
                // mkdir($options['dir'], 0777);
            }
            $this->_savePath = $options['dir'];
        } else {
            $this->_savePath = sys_get_temp_dir();
        }

        if (isset($options['prefix'])) {
            $this->prefix = $options['prefix'];
        }

        if (!empty($options['set_save_handler'])) {
            $this->registerSaveHandler();
        }
    }

    /**
     * @param $sessId
     * @return string
     */
    protected function _filename($sessId)
    {
        return "$this->_savePath/{$this->prefix}$sessId";
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
        $file = $this->_filename($sessId);
        if (file_exists($file)) {
            $lifetime = (int) ini_get("session.gc_maxlifetime");
            if (filemtime($file) + $lifetime > time()) {
                return (string) file_get_contents($file);
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
        $file = $this->_filename($sessId);
        return false !== file_put_contents($file, $data, LOCK_EX);
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
        $file = $this->_filename($sessId);

        if (file_exists($file)) { // race?
            unlink($file);
        }

        return true;
    }

    /**
     * @param int $maxlifetime
     * @return bool|mixed
     */
    public function gc($maxlifetime)
    {
        $mask = $this->_filename("*");

        foreach (glob($mask) as $file) {
            if (filemtime($file) + $maxlifetime <= time()) {
                unlink($file);
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
     * no-op pre tento handler
     * @param $ttl
     * @return $this|mixed
     */
    public function setLifetime($ttl)
    {
        trigger_error("Setting custom lifetime is not supported in this handler");
        return $this;
    }

    /**
     * no-op pre tento handler
     * @return mixed|null
     */
    public function getLifetime()
    {
        return null;
    }
}