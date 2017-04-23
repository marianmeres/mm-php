<?php
class MM_View_Namespaced_customHelperOne extends \MM\View\Helper
{
    public function __invoke()
    {
        return 1;
    }
}