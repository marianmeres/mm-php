<?php
/**
 * User: mm
 * Date: 27/09/15
 * Time: 15:54
 */

namespace MM\Model;


class AbstractPersistentModel extends AbstractModel
{
    /**
     * FLag indicating whether this model instance was marked as "new"
     * @var bool
     */
    protected $__isNew = true;

    /**
     * FLag indicating whether this model instance was marked as "deleted"
     * @var bool
     */
    protected $__isDeleted = false;

    /**
     * @throws Exception
     */
    protected function _init()
    {
        parent::_init();

        // tu assrtneme, ze ak sme composit, tak ziadem z klucov sa nevola "id"
        // islo by to hacknut, ale tolko sumu by to vnieslo, ze to nestoji za to...

        $idInfo = $this->__getIdInfo();
        if (count($idInfo) > 1 && in_array('id', $idInfo)) {
            throw new Exception(
                "Key 'id' is not allowed in persistent composite pk models"
            );
        }
    }

    /**
     * @return array
     */
    public function __getIdInfo()
    {
        if (!array_key_exists('id', $this->_data)) {
            throw new \RuntimeException(
                "Seems like " . __METHOD__ . " should be extended for " . __CLASS__
            );
        }
        return ["id"];
    }

    /**
     * (proxy) method to get "id" of the model. Id means something which will
     * uniquely identify this instance in storage. Typically primary key.
     * May return single value or assoc array (composit pk).
     *
     * @param bool $forceAsAssoc
     * @return array|mixed
     */
    public function getId($forceAsAssoc = false)
    {
        $info = $this->__getIdInfo();

        if (count($info) == 1) {
            $key = $info[0];
            if ($forceAsAssoc) {
                return [$key => $this->_data[$key]];
            }
            return $this->_data[$key];
        }

        $out = [];
        foreach ($info as $k) {
            $out[$k] = $this->$k;
        }
        ksort($out);

        return $out;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $info = (array) $this->__getIdInfo();

        if (null !== $id && count($info) != count((array) $id)) {
            throw new \InvalidArgumentException(
                "Id info definition vs id value length mismatch"
            );
        }

        if (!is_array($id)) {
            $id = array('id' => $id);
        }

        $check = array();

        foreach ($id as $k => $v) {
            if (!array_key_exists($k, $this->_data)) {
                throw new \InvalidArgumentException(
                    "Id info definition mismatch: key '$k' not found"
                );
            }
            // can't use setter here, because we're inside of it
            if ("id" == $k) {
                $this->_setRawValueAndMarkDirtyIfNeeded($k, $v);
            } else {
                $this->$k = $v;
            }
            $check[] = $k;
        }

        // setting single pk **usually** means, we're not new
        if (1 == count($id)) {
            $this->__setIsNew(false);
        }

        // sanity
        sort($info); sort($check);
        if ($info !== $check) {
            throw new \InvalidArgumentException(
                "Provided id data do not match the id definition"
            );
        }

        return $this;
    }

    /**
     * Toto prelezie id (single alebo composite) hodnoty a v sulade s aplikacnou
     * konvenciou pozrie ci su hodnoty ne/empty
     *
     * @return bool
     */
    public function hasId()
    {
        $id = $this->getId();

        if (is_array($id)) {
            foreach ($id as $k => $v) {
                if ("" == "$v") {
                    return false;
                }
            }
        }
        else if ("" == "$id") {
            return false;
        }

        return true;
    }

    /**
     * @param bool|true $flag
     * @return $this
     */
    public function __setIsNew($flag = true)
    {
        $this->__isNew = (bool) $flag;
        return $this;
    }

    /**
     * @return bool
     */
    public function __isNew()
    {
        return (bool) $this->__isNew;
    }

    /**
     * Alias
     * @param bool|true $flag
     * @return mixed
     */
    public function markNew($flag = true)
    {
        return $this->__setIsNew($flag);
    }

    /**
     * @return bool
     */
    public function __isDeleted()
    {
        return $this->__isDeleted;
    }

    /**
     * @param bool|true $flag
     * @return $this
     */
    public function __setIsDeleted($flag = true)
    {
        $this->__isDeleted = (bool) $flag;
        return $this;
    }

    /**
     * Alias
     * @param bool|true $flag
     * @return AbstractPersistentModel
     */
    public function markDeleted($flag = true)
    {
        return $this->__setIsDeleted($flag);
    }
}