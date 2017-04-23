<?php
/**
 * @author Marian Meres
 */
namespace MM\View;

/**
 * Class Helper
 * @package MM\View
 */
class Helper
{
    /**
     * @var View
     */
    protected $_view;

    /**
     * @param ViewAbstract $view
     */
    public function __construct(ViewAbstract $view = null)
    {
        if ($view) {
            $this->setView($view);
            $this->_init();
        }
    }

    /**
     * @param ViewAbstract $view
     * @return $this
     */
    public function setView(ViewAbstract $view = null)
    {
        $this->_view = $view;
        return $this;
    }

    /**
     * init hook
     */
    protected function _init() {}
}
