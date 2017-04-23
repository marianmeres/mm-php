<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Filter;

use MM\Form\FilterInterface;

/**
 * Class Trim
 * @package MM\Form\Filter
 */
class Trim implements FilterInterface
{
    /**
     * @var null|string
     */
    protected $_chars = null;

    /**
     * @param null $chars
     */
    public function __construct($chars = null)
    {
        if (null !== $chars) {
            $this->_chars = (string) $chars;
        }
    }

    /**
     * @param $value
     * @return string
     */
    public function filter($value)
    {
        if (null !== $this->_chars) {
            return trim($value, $this->_chars);
        }
        return trim($value);
    }
}