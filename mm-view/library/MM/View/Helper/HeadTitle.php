<?php
/**
 * @author Marian Meres
 */
namespace MM\View\Helper;

/**
 * Class HeadTitle
 * @package MM\View\Helper
 */
class HeadTitle extends ContainerOfStrings
{
    protected $_separator = " | ";
    protected $_unique = false;
    protected $_escape = true;
}