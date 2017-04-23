<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Filter;

use MM\Form\FilterInterface;

/**
 * Class Uppercase
 * @package MM\Form\Filter
 */
class Uppercase implements FilterInterface
{
    /**
     * @param $value
     * @return string
     */
    public function filter($value)
    {
        return strtoupper($value);
    }
}