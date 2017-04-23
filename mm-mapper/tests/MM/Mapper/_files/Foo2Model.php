<?php

namespace MM\Mapper\Test;

/**
 * User: mm
 * Date: 18/02/16
 * Time: 11:13
 */
class Foo2Model extends \MM\Model\AbstractPersistentModel
{
    protected $_data = [
        'id1' => null,
        'id2' => null,
        'name' => null
    ];

    public function __getIdInfo()
    {
        return ['id1', 'id2'];
    }

    public function getId($forceAsAssoc = false)
    {
        $out = [];
        foreach ($this->__getIdInfo() as $k) {
            $out[$k] = $this->_data[$k];
        }
        return $out;
    }
}