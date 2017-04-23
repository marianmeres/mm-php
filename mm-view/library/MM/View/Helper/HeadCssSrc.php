<?php
/**
 * @author Marian Meres
 */
namespace MM\View\Helper;

/**
 * Class HeadCssSrc
 * @package MM\View\Helper
 */
class HeadCssSrc extends ContainerOfStrings
{
    /**
     * @var bool
     */
    protected $_unique = true;

    /**
     * @var bool
     */
    protected $_escape = false;

    /**
     * @return string
     */
    public function toString()
    {
        $out = '';
        foreach ($this->_container as $src) {
            $out .= "<link href='$src' rel='stylesheet'>\n";
        }
        return $out;
    }
}
