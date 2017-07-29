<?php

namespace MM\Mapper;

use MM\Mapper\Dao\AbstractDao;
use MM\Mapper\Serializer\PhpArray;
use MM\Model\AbstractPersistentModel;
use MM\Mapper\Serializer\AbstractSerializer;
use MM\Util\ClassUtil;

/**
 * Class Mapper
 * @package MM\Mapper
 */
class Mapper
{
    /**
     * @var AbstractDao
     */
    protected $_dao;

    /**
     * @var AbstractSerializer
     */
    protected $_serializer;

    /**
     * @var string
     */
    public $modelFqn;

    /**
     * Callback pouzity na factory modela (volitelny)
     * @var \Callable
     */
    protected $_modelFactoryCallback;

    /**
     * Staticka (! pozor v testoch) identity mapa
     * @see http://en.wikipedia.org/wiki/Identity_map_pattern
     * Defacto ide o obycajnu in-memory kes instacii
     *
     * @feature: ak bude null, tak cele identity mapovanie bude deaktivovane
     * @var array
     */
    protected static $_identityMap = array();

    /**
     * Flagy indikujuce ci bude mapper automaticky validovat model pri find/save
     * @var boolean
     */
    public $validateModelOnFind = false;

    /**
     * @var bool
     */
    public $validateModelOnSave = false;

    /**
     * Flag indikujuci ci pri delete zmazeme identitu, alebo len oznacime objekt
     * ako deleted
     *
     * @var bool
     */
    public $removeIdentityOnDelete = true;

    /**
     * @param array $options
     */
    public function __construct(array $options = null)
    {
        ClassUtil::setOptions($this, $options);
        $this->_init();
    }

    /**
     *
     */
    protected function _init()
    {
        $this->_serializer = new PhpArray();
    }

    /**
     * Na pouzitie v testoch primarne
     */
    public static function resetToOutOfTheBoxState()
    {
        self::$_identityMap = array();
    }

    /**
     * @param AbstractDao $dao
     * @return $this
     */
    public function setDao(AbstractDao $dao)
    {
        $this->_dao = $dao;
        return $this;
    }

    /**
     * @return AbstractDao
     * @throws Exception
     */
    public function getDao()
    {
        if (!$this->_dao) {
            throw new Exception("Dao instance not set");
        }
        return $this->_dao;
    }

    /**
     * @param AbstractSerializer $serializer
     * @return $this
     */
    public function setSerializer(AbstractSerializer $serializer)
    {
        $this->_serializer = $serializer;
        return $this;
    }

    /**
     * @return AbstractSerializer
     * @throws Exception
     */
    public function getSerializer()
    {
        if (!$this->_serializer) {
            throw new Exception("Serializer instance not set");
        }
        return $this->_serializer;
    }

    /**
     * @param \Closure|null $cb
     * @return $this
     */
    public function setModelFactoryCallback(\Closure $cb = null)
    {
        $this->_modelFactoryCallback = $cb;
        return $this;
    }

    /**
     * @param $modelId
     * @param bool $assert
     * @return AbstractPersistentModel|null
     * @throws Exception
     * @throws \MM\Model\Exception
     */
    public function find($modelId, $assert = true)
    {
        // dufam, ze sa mylim, ale tento na prvy pohlad nevinny sanity check
        // moze byt za istych okolnosti trosku tricky...
        if (null === $modelId) {
            return null;
        }

        $dao = $this->getDao();
        $serializer = $this->getSerializer();

        if ($model = $this->getIdentity($this->getNormalizedIdentityMapId($modelId))) {
            return $model;
        }

        if (is_scalar($modelId)) {
            $idData = ['id' => $modelId];
        } else {
            $idData = $modelId;
        }

        $row = $dao->read($idData);
        if (null === $row) {
            if ($assert) {
                throw new Exception(sprintf(
                    "Model '%s' not found", json_encode($modelId)
                ));
            }
            return null;
        }

        $data = $serializer->unserialize($row);

        $model = $this->data2model($data);

        $model->__setIsNew(false);

        if ($this->validateModelOnFind) {
            $model->validate();
        }

        $model->markClean();
        $this->saveIdentity($model);

        return $model;
    }

    /**
     * @param array $data
     * @return AbstractPersistentModel
     * @throws Exception
     */
    public function data2model(array $data, $saveIdentity = false)
    {
        /** @var AbstractPersistentModel $model */

        if ($this->_modelFactoryCallback) {
            $factory = $this->_modelFactoryCallback;
            $model = $factory($data);
            if (!$model instanceof AbstractPersistentModel) {
                $msg = "Invalid model instance%s created by factory";
                if (is_object($model)) {
                    $msg = sprintf($msg, " '" . get_class($model) . "'");
                } else {
                    $msg = sprintf($msg, "");
                }
                throw new Exception($msg);
            }
        } else {
            $model = new $this->modelFqn;
            // sanity check...
            if (!$model instanceof AbstractPersistentModel) {
                throw new Exception(
                    "Invalid model class name '$this->modelFqn' configured, expecting "
                    . "instance of MM\Model\AbstractPersistentModel"
                );
            }

            $model->set($data);
        }

        $saveIdentity && $this->saveIdentity($model);

        return $model;
    }

    /**
     * "fetch one" sugar on top of fetchAll
     *
     * @param $where
     * @param bool $assert
     * @return null|AbstractPersistentModel
     * @throws Exception
     */
    public function findBy($where, $assert = true)
    {
        $all = $this->fetchAll($where, ['limit' => 1], false);

        if (empty($all)) {
            if ($assert) {
                throw new Exception(sprintf(
                    "Model '%s' not found", json_encode($where)
                ));
            }
            return null;
        }

        return current($all);
    }

    /**
     * @param mixed $where
     * @param array|null $options
     * @return array
     * @throws Exception
     * @throws \MM\Model\Exception
     */
    public function fetchAll($where, array $options = null,
                             $overwriteExistingIdentity = false)
    {
        $dao = $this->getDao();
        $serializer = $this->getSerializer();
        $out = [];
        $im = $this->getIdentityMap();

        $rows = $dao->fetchAll($where, $options);
        foreach ($rows as $row) {
            $data = $serializer->unserialize($row);
            $model = $this->data2model($data);
            $model->__setIsNew(false);

            // ak existuje uz zaznam v IM, ale nesmieme ho prepisat, tak si
            // ho fakeovo podhodime a vyssie ignorujeme, aby nasledny flow
            // fungoval akoze nic...
            $id = $this->getNormalizedIdentityMapId($model->getId());
            if (isset($im[$id]) && !$overwriteExistingIdentity) {
                $model = $im[$id];
            }

            if ($this->validateModelOnFind) {
                $model->validate();
            }

            $model->markClean();
            $this->saveIdentity($model);

            // hm... id based alebo nie?
            //$out[$id] = $model;
            $out[] = $model;
        }

        return $out;
    }

    /**
     * @param $where
     * @return int
     */
    public function fetchCount($where)
    {
        return $this->getDao()->fetchCount($where);
    }

    /**
     * @param AbstractPersistentModel $model
     * @param array $options
     * @return AbstractPersistentModel
     * @throws Exception
     * @throws \Exception
     * @throws \MM\Model\Exception
     */
    public function save(AbstractPersistentModel $model, array $options = array())
    {
        $dao = $this->getDao();
        $serializer = $this->getSerializer();

        if ($this->validateModelOnSave) {
            $model->validate();
        }

        // aj ked je cisty, do im ho ulozime... (interne ak nema idecko sa nic
        // neulozi)
        $this->saveIdentity($model);

        if (!$model->isDirty()) {
            return $model;
        }

        // tu si ulozime bool, ze ci ocakavame autoincrement v akcii...
        // je to pomerne zasadna info pre dalsi flow
        $isAutoIncrementExpected = $dao->autoIncrement && null === $model->getId();

        // note: transakcie som uplne vyhodil, lebo v tejto urovni nedavaju zmysel
        // (insert aj update je atomicky sam osobe)

        if ($model->__isNew()) {
            $data = $serializer->serialize($model->toArray());
            $result = $dao->create($data);
            if ($isAutoIncrementExpected) {
                $model->setId($result); // create vracia last insert id
            }
        } else {
            $data = $serializer->serialize($model->dirtyData());
            $dao->update($model->getId(true), $data); // getId(true) je dolezity
        }

        $model->__setIsNew(false);
        $model->markClean();

        // ulozime idenitu (toto robime vzdy, bez ohladu na db write)
        $this->saveIdentity($model);

        return $model;
    }

    /**
     * @param $idOrModel
     * @param array|null $options
     * @return AbstractPersistentModel|null
     * @throws Exception
     */
    public function delete($idOrModel, array $options = null)
    {
        $dao = $this->getDao();

        /** @var AbstractPersistentModel $model */

        if ($idOrModel instanceof $this->modelFqn) {
            $model = $idOrModel;
            $id = $model->getId();
        } else {
            $model = null;
            $id = $idOrModel;
        }

        if (is_scalar($id)) {
            $idData = ['id' => $id];
        } else {
            $idData = $id;
        }

        $dao->delete($idData);

        if ($model) {
            $model->__setIsDeleted(true);
        }

        if ($this->removeIdentityOnDelete) {
            $this->deleteIdentity($model ? $model : $id);
        }

        return $model; // moze byt null ak parameter bol iba id
    }

    /***************************************************************************
     * IDENTITY MAP API
     **************************************************************************/

    /**
     * @param $tableName
     */
    public static function flushIdentityMap($tableName)
    {
        // flushujeme iba ak je array...
        // Note: null ma specialny vyznam
        if (is_array(self::$_identityMap)) {
            self::$_identityMap[$tableName] = array();
        }
    }

    /**
     *
     */
    public static function disableIdentityMap()
    {
        self::$_identityMap = null;
    }

    /**
     *
     */
    public static function enableIdentityMap()
    {
        self::$_identityMap = array();
    }

    /**
     * @return bool
     */
    public function isIdentityMapEnabled()
    {
        return is_array(self::$_identityMap);
    }

    /**
     * @param $id
     * @param bool $throwOnEmptyIdValue
     * @return array|bool|string
     * @throws Exception
     */
    public function getNormalizedIdentityMapId($id, $throwOnEmptyIdValue = true)
    {
        $table = $this->getDao()->tableName;

        $msg = "Trying to build normalized identity map id for '$table' but value "
             . "for '%s' is evaluated as empty string which is not supported; "
             . "Hint: perhaps model was not saved yet";

        if (is_array($id)) {
            ksort($id);

            // toto len pre poriadok: hodnoty vyustene do prazdneho stringu
            // (typicky nully alebo prazdny string samotny) tu jednoducho nebudeme
            // podporovat. Ked ukldame identitu by to tak ci onak nemalo nastat,
            // ale toto moze byt volane aj odinokadial...
            foreach ($id as $k => $v) {
                if ("" == "$v") {
                    if (!$throwOnEmptyIdValue) {
                        return false;
                    }
                    throw new Exception(sprintf($msg, $k));
                }

                // toto je kozmeticka vec, ale aby nahodou nedoslo k nejednoznacnosti
                // v nizsom encode - vsetky hodnoty id castujeme na string
                $id[$k] = "$v";
            }

            // tento implode je potencialne ambiguos... ['a', 'b-c'] vs ['a-b', 'c']
            // return implode("-", $id);
            // preto sa nebudeme drbkat a:
            return json_encode($id);
        }

        $id = (string) $id;

        // podobne ako vyssie, aj tu dorabam explicitny sanity check, ktory
        // pri prazdnych ideckach padne... pointa je, ze nechcem ticho neulozit
        // od im, ked z vonka vyzera, ze vsetko dobre dopadlo
        if ("" == "$id") {
            if (!$throwOnEmptyIdValue) {
                return false;
            }
            throw new Exception(sprintf(
                $msg, 'single-scalar-id (actual property name not known here)'
            ));
        }

        return $id;
    }

    /**
     * @param AbstractPersistentModel $model
     * @return $this
     * @throws Exception
     */
    public function saveIdentity(AbstractPersistentModel $model)
    {
        if (!$model instanceof $this->modelFqn) {
            throw new Exception(
                "Trying to save identity of wrong instance; Expecting "
                . "'{$this->modelFqn}' instead of '" . get_class($model) . "'"
            );
        }

        // null means im is completely disabled
        if (null === self::$_identityMap) {
            return $this;
        }

        // ak nema idecko tak ticho nic neukladame
        if (!$model->hasId()) {
            return $this;
        }

        $table = $this->getDao()->tableName;
        $id = $this->getNormalizedIdentityMapId($model->getId());

        if ($id !== false) {
            self::$_identityMap[$table][$id] = $model;
        }

        return $this;
    }

    /**
     * @param $idOrModel
     * @return $this
     * @throws Exception
     */
    public function deleteIdentity($idOrModel)
    {
        /** @var AbstractPersistentModel|int $idOrModel */
        $id = $idOrModel instanceof $this->modelFqn
            ? $idOrModel->getId() : $idOrModel;

        $table = $this->getDao()->tableName;
        if (false === ($id = $this->getNormalizedIdentityMapId($id, false))) {
            return $this;
        }

        unset(self::$_identityMap[$table][$id]);

        return $this;
    }

    /**
     * @param $id
     * @return null|AbstractPersistentModel
     * @throws Exception
     */
    public function getIdentity($id)
    {
        $table = $this->getDao()->tableName;
        if (false === ($id = $this->getNormalizedIdentityMapId($id, false))) {
            return null;
        }

        if (!empty(self::$_identityMap[$table][$id])) {
            return self::$_identityMap[$table][$id];
        }

        return null;
    }

    /**
     *
     */
    public static function resetIdentityMap()
    {
        self::$_identityMap = [];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getIdentityMap()
    {
        $im = self::$_identityMap;
        $table = $this->getDao()->tableName;

        if (isset($im[$table])) {
            return $im[$table];
        }

        return [];
    }

    /**
     * @param bool $dataOnly
     * @return array
     */
    public static function dumpIdentityMap($dataOnly = false)
    {
        $im = self::$_identityMap;

        if (!$dataOnly) {
            return $im;
        }

        $out = [];

        foreach($im as $tableName => $data) {
            if (!isset($out[$tableName])) {
                $out[$tableName] = array();
            }
            /** @var AbstractPersistentModel $model */
            foreach ($data as $id => $model) {
                $out[$tableName][$id] = $model->toArray();
            }
        }
        return $out;
    }
}
