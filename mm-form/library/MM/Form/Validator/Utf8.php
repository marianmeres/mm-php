<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Validator;

class Utf8 extends AbstractValidator
{
    /**
     * @var string
     */
    protected $_message = "__form_not_valid_utf8";

    /**
     * @var array
     */
    protected $_messageTemplates = array(
        '__form_not_valid_utf8' => "Value seems not to be encoded in UTF-8",
    );

    /**
     * @param $value
     * @param null $context
     * @return bool
     */
    public function isValid($value, $context = null)
    {
        return mb_check_encoding($value, 'UTF-8');
    }
}