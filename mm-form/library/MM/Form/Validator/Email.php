<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Validator;

class Email extends AbstractValidator
{
    /**
     * @var string
     */
    protected $_message = "__form_invalid_email";

    /**
     * @var array
     */
    protected $_messageTemplates = array(
        '__form_invalid_email' => "Email '{{value}}' appears to be invalid",
    );

    /**
     * @param $value
     * @param null $context
     * @return mixed
     */
    public function isValid($value, $context = null)
    {
        $this->_value = $value;
        return filter_var($value, \FILTER_VALIDATE_EMAIL);
    }
}