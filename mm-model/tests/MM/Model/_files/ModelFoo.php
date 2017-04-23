<?php

class MM_Model_ModelFoo extends MM\Model\AbstractModel
{
    protected $_data = array(
        'id'     => null,
        'name'   => null,
        'bull'   => null,
        'isShit' => true,
    );

    public function setName($name)
    {
        $this->_data['name'] = $name;
        $this->_data['id']   = strtolower($name);
        return $this;
    }

    // "some" key does not exists
    public function getSome()
    {
        return 'some';
    }

    public function getIsShit()
    {
        return (int) $this->_data['isShit'];
    }

}