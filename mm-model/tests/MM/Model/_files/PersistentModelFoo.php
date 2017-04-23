<?php

class MM_Model_PersistentModelFoo extends MM\Model\AbstractPersistentModel
{
    protected $_data = array(
        'id'     => null,
        'name'   => null,
        'bull'   => null,
        'isShit' => true,
    );

}

class MM_Model_PersistentModelFoo2 extends MM\Model\AbstractPersistentModel
{
    protected $_data = array(
        'user_id'     => null,
        'some_id'   => null,
        'bull'   => null,
    );


    public function __getIdInfo()
    {
        return ['user_id', 'some_id'];
    }
}