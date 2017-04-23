<?php
namespace MM\View\Namespaced;

class Three extends \MM\View\Helper
{
    public function __invoke()
    {
        return 3;
    }
}