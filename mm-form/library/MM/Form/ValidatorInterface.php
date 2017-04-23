<?php
/**
 * @author Marian Meres
 */
namespace MM\Form;

use MM\Util\TranslateInterface;

interface ValidatorInterface
{
    public function isValid($value, $context = null);

    public function getMessage();

    public function setBreakChainOnFailure($flag = true);

    public function getBreakChainOnFailure();

    public function setTranslate(TranslateInterface $translate = null);

    public function getTranslate();
}