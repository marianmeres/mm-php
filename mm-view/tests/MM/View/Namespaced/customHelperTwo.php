<?php
namespace MM\View\Namespaced;

class customHelperTwo extends \MM\View\Helper
{
    public function __invoke()
    {
        return 2;
    }
}