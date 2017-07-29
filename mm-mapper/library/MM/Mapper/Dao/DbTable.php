<?php
/**
 * User: mm
 * Date: 17/02/16
 * Time: 22:54
 */

namespace MM\Mapper\Dao;

use MM\Util\DbUtilPdo;

class DbTable extends AbstractDao
{
    /**
     * @var string
     */
    public $tableName;

    /**
     * @var bool
     */
    public $autoIncrement = false;

    /**
     * @var string|array
     */
    protected $_idInfo;

    /**
     * @var DbUtilPdo
     */
    protected $_db;

    /**
     * @param DbUtilPdo|null $db
     * @return $this
     */
    public function setDb(DbUtilPdo $db = null)
    {
        $this->_db = $db;
        return $this;
    }

    /**
     * @return DbUtilPdo
     * @throws Exception
     */
    public function getDb()
    {
        $this->_assertRequired();
        return $this->_db;
    }

    /**
     * quote value
     *
     * @param $val
     * @return string
     * @throws Exception
     */
    public function qv($val)
    {
        $this->_assertRequired();
        return $this->_db->qv($val);
    }

    /**
     * qoute identifier
     *
     * @param $val
     * @return string
     * @throws Exception
     */
    public function qi($val)
    {
        $this->_assertRequired();
        return $this->_db->qi($val);
    }

    /**
     * @param $idInfo
     * @return $this
     * @throws Exception
     */
    public function setIdInfo($idInfo)
    {
        if (!is_string($idInfo) && !is_array($idInfo)) {
            throw new Exception("Unrecognized id (pk) description");
        }
        is_array($idInfo) && sort($idInfo);

        // interne vzdy normalizujeme na array
        $this->_idInfo = (array) $idInfo;

        return $this;
    }

    /**
     * @throws Exception
     */
    protected function _assertRequired()
    {
        if (empty((string) $this->tableName)) {
            throw new Exception("Table name empty");
        }

        if (!$this->_db) {
            throw new Exception("DbUtil not set");
        }

        if (empty($this->_idInfo)) {
            throw new Exception("Id info not provided");
        }

        if ($this->autoIncrement && count($this->_idInfo) > 1) {
            throw new Exception("Auto increment is not supported on composite pks");
        }
    }

    /**
     * @param array $idData
     * @throws Exception
     */
    protected function _assertNotEmptyIdData(array $idData)
    {
        if (empty($idData)) {
            throw new Exception("Id data empty");
        }

        foreach ($idData as $k => $v) {
            // toto cisto technicky nie je problem, ale v realnom use case-y
            // to ako problem budem chapat
            if ("$v" == "") {
                throw new Exception(
                    "Id data for key '$k' (table '{$this->tableName}') is "
                    . "evaluated as empty string which is not supported"
                );
            }
        }
    }

    /**
     * @param array $idData
     * @throws Exception
     */
    protected function _assertIdDataMatchIdInfo(array $idData)
    {
        $idInfo = array_keys($idData);
        sort($idInfo);

        if ($idInfo != $this->_idInfo) {
            throw new Exception(sprintf(
                "Id data '%s' do not match id description '%s'",
                json_encode($idInfo), json_encode($this->_idInfo)
            ));
        }
    }

    /**
     * @param array $idData
     * @param array|null $options
     * @return int
     * @throws Exception
     */
    public function exists(array $idData, array $options = null)
    {
        $this->_assertRequired();
        $this->_assertIdDataMatchIdInfo($idData);
        $this->_assertNotEmptyIdData($idData);
        return 0 != $this->_db->fetchCount($this->tableName, $idData);
    }

    /**
     * @param array $data
     * @param array|null $options
     * @return int|bool
     * @throws Exception
     */
    public function create(array $data, array $options = null)
    {
        $this->_assertRequired();

        $_manualId = null; // pg seq fix

        // pgsql hack... ak mame autoincrement so singlepk a pk nebol
        // setnuty (je null) tak ho musime z dat unsetnut, lebo postgres
        // inak pinda, lebo serial nesmie byt null
        if ($this->_db->isPgsql() && $this->autoIncrement) {
            $pkCol = $this->_idInfo[0]; // autoincrement moze byt len jeden, teda 0 index
            if (array_key_exists($pkCol, $data)) {
                if (null === $data[$pkCol]) {
                    unset($data[$pkCol]);
                } else {
                    $_manualId = (int) $data[$pkCol];
                }
            }
        }

        $affected = $this->_db->insert($this->tableName, $data);

        if ($this->autoIncrement) {
            $seqName = null;
            if ($this->_db->isPgsql()) {
                $seqName = $this->tableName . "_" . $this->_idInfo[0] . "_seq";
            }
            $lasId = $this->_db->lastInsertId($seqName);

            // pg seq fix
            if ($this->_db->isPgsql() && $_manualId) {
                $this->_db->execute(sprintf(
                    "alter sequence %s restart with %d",
                    $this->_db->qi($seqName), $_manualId + 1
                ));
            }

            return $lasId;
        }

        return $affected > 0;
    }

    /**
     * @param array $idData
     * @param array|null $options
     * @return array|null
     * @throws Exception
     */
    public function read(array $idData, array $options = null)
    {
        $this->_assertRequired();
        $this->_assertIdDataMatchIdInfo($idData);
        $this->_assertNotEmptyIdData($idData);
        return $this->_db->fetchRow('*', $this->tableName, $idData);
    }

    /**
     * @param mixed $where
     * @param array|null $options
     * @return array
     * @throws Exception
     */
    public function fetchAll($where, array $options = null)
    {
        $this->_assertRequired();
        return $this->_db->fetchAll('*', $this->tableName, $where, $options);
    }

    /**
     * @param $where
     * @param array|null $options
     * @return int
     */
    public function fetchCount($where, array $options = null)
    {
        $this->_assertRequired();
        return $this->_db->fetchCount($this->tableName, $where, $options);
    }

    /**
     * @param array $idData
     * @param $data
     * @param array|null $options
     * @return int
     * @throws Exception
     */
    public function update(array $idData, $data, array $options = null)
    {
        $this->_assertRequired();
        $this->_assertIdDataMatchIdInfo($idData);
        $this->_assertNotEmptyIdData($idData);
        return $this->_db->update($this->tableName, $data, $idData);
    }

    /**
     * @param array $idData
     * @param array|null $options
     * @return int|string|void
     * @throws Exception
     */
    public function delete(array $idData, array $options = null)
    {
        $this->_assertRequired();
        $this->_assertIdDataMatchIdInfo($idData);
        $this->_assertNotEmptyIdData($idData);
        return $this->_db->delete($this->tableName, $idData);
    }


    /**
     * @param array|null $options
     * @return $this
     * @throws Exception
     */
    public function begin(array $options = null)
    {
        $this->_assertRequired();
        $this->_db->begin();
        return $this;
    }

    /**
     * @param array|null $options
     * @return $this
     * @throws Exception
     */
    public function rollback(array $options = null)
    {
        $this->_assertRequired();
        $this->_db->rollback();
        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function commit(array $options = null)
    {
        $this->_assertRequired();
        $this->_db->commit();
        return $this;
    }
}