<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 10/07/15
 * Time: 11:51
 */

namespace MM\ContentComponent;

use MM\ContentComponent\Dao\AbstractDao;
use MM\ContentComponent\Serializer\AbstractSerializer;
use MM\Util\ClassUtil;

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
    public $modelFqn = "\MM\ContentComponent\Model";

    /**
     * @param array $options
     */
    public function __construct(array $options = null)
    {
        if ($options) {
            ClassUtil::setOptions($this, $options);
        }
        $this->_init();
    }

    /**
     *
     */
    protected function _init()
    {
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
     * @return AbstractDao|Dao\File
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
     * @param $componentId
     * @param bool $assert
     * @return Model|null
     * @throws Exception
     * @throws \MM\Model\Exception
     */
    public function find($componentId, $assert = true)
    {
        $dao = $this->getDao();
        $serializer = $this->getSerializer();

        // read raw data
        $raw = $dao->read($componentId);
        if (null === $raw) {
            if ($assert) {
                throw new Exception("Component '$componentId' not found");
            }
            return null;
        }

        // unserialize raw data
        $unserialized = $serializer->unserialize($raw);

        // sanity check
        if (!is_array($unserialized)
            || !isset($unserialized['_data'])
            || !is_array($unserialized['_data'])
            || !isset($unserialized['_attrs'])
            || !is_array($unserialized['_attrs'])
        ) {
            throw new Exception(
                "Invalid unserialize result; expecting array with "
                . "'_data' and '_attrs' array values"
            );
        }

        // create model instance
        /** @var Model $model */
        $model = new $this->modelFqn;

        // sanity check...
        if (!$model instanceof Model) {
            throw new Exception(
                "Invalid model class name '$this->modelFqn' provided, expecting "
                . "instance of MM\ContentComponent\Model"
            );
        }

        $model->set($unserialized['_data']);
        $model->initDataAttributes($unserialized['_attrs']);

        // make sure id is correct and in sync
        $model->setId($componentId);

        //
        $model->markClean();

        // return model
        return $model;
    }

    /**
     * @param Model $model
     * @return Model
     * @throws Exception
     * @throws \MM\Model\Exception
     */
    public function save(Model $model)
    {
        $dao = $this->getDao();
        $serializer = $this->getSerializer();

        $cid = $model->getId();
        if (empty($cid)) {
            throw new Exception('Empty component id, aborting save...');
        }

        if (!$model->isDirty()) {
            return $model;
        }

        // serialize model
        $serialized = $serializer->serialize($model);
        //prx($serialized);

        if ($dao->exists($cid)) {
            $dao->update($cid, $serialized);
        } else {
            $dao->create($cid, $serialized);
        }

        $model->markClean();

        return $model;
    }

    /**
     * @param Model $model
     * @param $newComponentId
     * @return Model
     * @throws Exception
     */
    public function saveAs(Model $model, $newComponentId)
    {
        $model->setId($newComponentId);
        return $this->save($model);
    }

    /**
     * @param $componentId
     * @return $this
     * @throws Exception
     */
    public function delete($componentId)
    {
        $this->getDao()->delete($componentId);
        return $this;
    }
}