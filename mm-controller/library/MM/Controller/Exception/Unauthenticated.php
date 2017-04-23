<?php
namespace MM\Controller\Exception;
use MM\Controller\Exception;
class Unauthenticated extends Exception
{
    protected $code = 401;
    protected $message = "Credentials not authenticated";
}