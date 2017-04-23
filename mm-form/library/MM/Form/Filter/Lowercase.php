<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Filter;

use MM\Form\FilterInterface;

/**
 * Class Lowercase
 * @package MM\Form\Filter
 */
class Lowercase implements FilterInterface
{
    /**
     * @param $value
     * @return string
     */
    public function filter($value)
    {
        return strtolower($value);
    }
}