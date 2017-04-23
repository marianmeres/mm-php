<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Validator;

class Required extends AbstractValidator
{
    /**
     * @var string
     */
    protected $_message = "__form_input_required";

    /**
     * @var array
     */
    protected $_messageTemplates = array(
        '__form_input_required' => "Value is required",
    );

    /**
     * @param $value
     * @param null $context
     * @return bool
     */
    public function isValid($value, $context = null)
    {
        return "" !== trim($value);
    }
}