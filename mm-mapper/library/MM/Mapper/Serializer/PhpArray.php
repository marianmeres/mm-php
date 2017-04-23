<?php
/**
 * User: mm
 * Date: 17/02/16
 * Time: 22:56
 */

namespace MM\Mapper\Serializer;


/**
 * Class PhpArray
 * @package MM\Mapper\Serializer
 *
 * Tento serializer realne nerobi ziadnu robotu
 */
class PhpArray extends AbstractSerializer
{
    /**
     * @param array $data
     * @param array $options
     * @return array
     */
    public function serialize(array $data, array $options = array())
    {
        return $data;
    }

    /**
     * @param $data
     * @param array $options
     * @return mixed
     */
    public function unserialize($data, array $options = array())
    {
        return $data;
    }
}