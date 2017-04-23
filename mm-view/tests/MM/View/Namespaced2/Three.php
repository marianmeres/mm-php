<?php
namespace MM\View\Namespaced2;

class Three extends \MM\View\Helper
{
    public function __invoke()
    {
        return 33;
    }
}