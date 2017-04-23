<?php
// http://www.php.net/manual/en/ref.math.php#55173
function median()
{
    $args = func_get_args();

    switch(func_num_args())
    {
        case 0:
            trigger_error('median() requires at least one parameter', E_USER_WARNING);
            return false;
            break;

        case 1:
            $args = array_pop($args);
            // fallthrough

        default:
            if(!is_array($args)) {
                trigger_error('median() requires a list of numbers to operate on or an array of numbers', E_USER_NOTICE);
                return false;
            }

            sort($args);
           
            $n = count($args);
            $h = intval($n / 2);

            if($n % 2 == 0) {
                $median = ($args[$h] + $args[$h-1]) / 2;
            } else {
                $median = $args[$h];
            }

            break;
    }
   
    return $median;
}