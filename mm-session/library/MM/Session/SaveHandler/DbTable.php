<?php
/**
 * @author Marian Meres
 */
namespace MM\Session\SaveHandler;

use MM\Session\Exception;
use MM\Session\SaveHandlerInterface;

use MM\Util\DbUtilPdo;
use MM\Util\ClassUtil;

/**
 * Session db save handler... ocakava DbUtilPdo util...
 */
class DbTable implements SaveHandlerInterface
{
    /**
     * @var DbUtilPdo
     */
    protected $_dbu;

    /**
     * Aditional key=>value data stored directly as db table columns. Typically
     * "user_id" for example.
     *
     * @var array
     */
    protected $_customFields = [];

    /**
     * @var Callable|null
     */
    public $logger;

    /**
     * Read only helper meta data
     * @var array
     */
    public static $tableDefinition = array(
        // definicia         => hodnota
        'table_name'         => '_session',
        'column_id'          => 'id',
        'column_data'        => 'data',
        // najvyssia autorita na vyhodnodnotenie ci je sessna v danom case platna
        // (a rovnako na garbage collect)
        'column_valid_until' => 'valid_until', // unix seconds
        // zivotnost sessiony v sekundach, defaultuje to "session.gc_maxlifetime"
        // NOTE: tento field sluzi ako meta info na incrementovanie vyssieho
        //       valid_until pri write (keby sa nedrzala priamo per row, tak custom
        //       rememberMe featury by neboli mozne)
        'column_lifetime' => 'lifetime',
    );

    /**
     * ttl seconds; valid_until sa bude incrementovat o tuto hodnotu
     *
     * 0 ma specialny vyznam - pri inserte bude defaultovat
     * do php "session.gc_maxlifetime"
     *
     * @var int
     */
    protected $_lifetime = 0;

    /**
     * Convenience shortcut
     * @return mixed
     */
    public function getTableName()
    {
        return self::$tableDefinition['table_name'];
    }

    /**
     * @return string
     */
    public static function getDefaultSql()
    {
        $d = self::$tableDefinition;
        return "
DROP TABLE IF EXISTS $d[table_name];
CREATE TABLE $d[table_name] (
    $d[column_id] char(32),
    $d[column_valid_until] int not null,
    $d[column_lifetime] int not null default 0,
    $d[column_data] text,
    PRIMARY KEY($d[column_id])
);
CREATE INDEX $d[table_name]_valid_until ON $d[table_name] ($d[column_valid_until]);
";
    }

    /**
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $register = !empty($options['set_save_handler']);
        unset($options['set_save_handler']);

        if ($options) {
            ClassUtil::setOptions($this, $options);
        }

        if ($register) {
            $this->registerSaveHandler();
        }
    }

    /**
     * @param DbUtilPdo $dbu
     * @return $this
     */
    public function setDbu(DbUtilPdo $dbu)
    {
        $this->_dbu = $dbu;
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setCustomField($key, $value)
    {
        $this->_customFields[$key] = $value;
        return $this;
    }

    /**
     * @param $key
     * @param bool|false $strict
     * @return bool|mixed
     * @throws \Exception
     */
    public function getCustomField($key, $strict = false)
    {
        if (array_key_exists($key, $this->_customFields)) {
            return $this->_customFields[$key];
        }

        if ($strict) {
            throw new Exception("Custom key '$key' not found");
        }

        return false;
    }

    /**
     * @return array
     */
    public function getCustomFields()
    {
        return $this->_customFields;
    }

    /**
     * Tries to find custom field in raw row
     * @param $row
     * @return $this
     */
    protected function _readCustom($row)
    {
        $regularFields = array_flip(self::$tableDefinition);

        foreach ($row as $k => $v) {
            if (!isset($regularFields[$k])) {
                $this->setCustomField($k, $v);
            }
        }

        return $this;
    }

    /**
     * Sets all custom values to null; preserves keys
     * @return $this
     */
    public function wipeCustom()
    {
        foreach ($this->_customFields as $k => &$v) {
            $v = null;
        }
        return $this;
    }

    /**
     * @param $savePath
     * @param $sessName
     * @return bool
     * @throws Exception
     */
    public function open($savePath, $sessName)
    {
        if (!$this->_dbu) {
            throw new Exception("Missing DbUtilPdo instance");
        }
        return true;
    }

    /**
     * @param string $sessId
     * @return string
     */
    public function read($sessId)
    {
        $d = self::$tableDefinition;
        $where = array($d['column_id'] => $sessId);

        if ($row = $this->_dbu->fetchRow("*", $d['table_name'], $where)) {
            if ($row[$d['column_valid_until']] >= time()) {
                $this->_readCustom($row);
                if (!$this->_postRead($sessId, $row)) {
                    $this->wipeCustom();
                    return '';
                }
                return $row[$d['column_data']];
            }
            $this->destroy($sessId);
        }

        return '';
    }

    /**
     * Read extension hook
     * @param $sessId
     * @param array $row
     * @return bool
     */
    protected function _postRead($sessId, array &$row)
    {
        return true;
    }

    /**
     * @param string $sessId
     * @param string $data
     * @return bool|mixed
     * @throws Exception
     */
    public function write($sessId, $data)
    {
        $d = self::$tableDefinition;
        $where = array($d['column_id'] => $sessId);
        $data  = array(
            $d['column_data'] => (string) $data,
        );

        foreach ($this->_customFields as $ck => $cv) {
            // sanity check - we don't want to colide with "regular" fields
            if (array_key_exists($ck, $data)) {
                throw new Exception("Invalid custom key '$ck'");
            }
            $data[$ck] = $cv;
        }

        if (!$this->_preWrite($sessId, $data)) {
            return false;
        }

        if ($row = $this->_dbu->fetchRow($d['column_lifetime'], $d['table_name'], $where)) {
            // ak mame "custom" lifetime, tak ho pouzijeme, inak ponechame povodny
            $data[$d['column_lifetime']] = !empty($this->_lifetime)
                ? $this->_lifetime
                : $row[$d['column_lifetime']];

            $data[$d['column_valid_until']] = time() + $data[$d['column_lifetime']];
            $result = (bool) $this->_dbu->update($d['table_name'], $data, $where);
        } else {
            $data[$d['column_id']] = $sessId;

            // bud "custom", alebo php ini default
            $data[$d['column_lifetime']]
                = !empty($this->_lifetime)
                ? $this->_lifetime
                : ini_get("session.gc_maxlifetime");

            $data[$d['column_valid_until']] = time() + $data[$d['column_lifetime']];
            $result = (bool) $this->_dbu->insert($d['table_name'], $data);
        }

        return $this->_postWrite($sessId, $data, $result);
    }

    /**
     * Write extension hook
     * @param $sessId
     * @param array $data
     * @return bool
     */
    protected function _preWrite($sessId, array &$data)
    {
        return true;
    }

    /**
     * Write extension hook
     * @param $sessId
     * @param array $data
     * @param $writeResult
     * @return mixed
     */
    protected function _postWrite($sessId, array &$data, $writeResult)
    {
        return $writeResult;
    }

    /**
     *
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
        $d = self::$tableDefinition;
        $where = array($d['column_id'] => $sessId);
        return (bool) $this->_dbu->delete($d['table_name'], $where);
    }

    /**
     * @param int $maxlifetime
     * @return bool|mixed
     */
    public function gc($maxlifetime)
    {
        $d = self::$tableDefinition;
        return (bool) $this->_dbu->delete($d['table_name'], sprintf(
            "$d[column_valid_until] <= %d", time()
        ));
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
     * Zaporna hodnota sa expiruje
     * @param $ttl
     * @return $this|mixed
     */
    public function setLifetime($ttl)
    {
        $this->_lifetime = (int) $ttl;
        return $this;
    }

    /**
     * @return int
     */
    public function getLifetime()
    {
        return $this->_lifetime;
    }

    /**
     * quick-n-dirty internal logger
     * @param $msg
     */
    protected function _log($msg, $data = null)
    {
        if ($this->logger instanceof \Closure) {
            call_user_func_array($this->logger, [$msg, $data]);
        }
    }
}