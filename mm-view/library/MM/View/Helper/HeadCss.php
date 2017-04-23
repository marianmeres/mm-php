<?php
/**
 * @author Marian Meres
 */
namespace MM\View\Helper;

/**
 * Class HeadCss
 * @package MM\View\Helper
 */
class HeadCss extends ContainerOfStrings
{
    /**
     * @var bool
     */
    protected $_unique = false;

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
        foreach ($this->_container as $css) {
            $out .= "<style type='text/css'>\n$css\n</style>\n";
        }
        return $out;
    }
}
