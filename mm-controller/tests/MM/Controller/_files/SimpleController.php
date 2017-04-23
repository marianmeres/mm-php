<?php

class SimpleController extends \MM\Controller\AbstractController
{
//    protected $_params = array(
//        'bull' => 'shit',
//    );

    protected function _init()
    {
        $this->testMode = 1;
        $this->params()->bull = 'shit';
    }

    protected function _preDispatch()
    {
        $this->some = 1;
    }

    public function testAction()
    {
        echo $this->some . 'test';
    }

    public function fooAction()
    {
        echo 'bar';
    }

    public function segmentAction()
    {
        $this->response()->setBody('c', true, 'c');
        $this->response()->setBody('b', true, 'b');
        $this->response()->setBody('a', true, 'a');
    }

    public function segmentAndEchoAction()
    {
        // note: echo ide do segmentu 'default'
        echo 'echo';

        // array access
        $this->_response['c'] = 'c';
        $this->_response['b'] = 'b';
        $this->_response['a'] = 'a';
    }

    public function dispatchResetAction()
    {
        // obe veci musia byt ignorovane, lebo postDispatch ich resetne
        echo 123;
        $this->_response['a'] = 'a';

        // dummy flag pre dispatch
        $this->resetResponseOnDispatch = true;
    }

    public function redirAction()
    {
        $this->redirect("http://nba.com");
    }

    public function secretAction()
    {
        $this->responseHeaders()->setStatusCode(403);
    }


    protected function _postDispatch()
    {
        if (!empty($this->resetResponseOnDispatch)) {
            $this->_response = null;
            return;
        }
        echo 2;
    }

    public function dumpHelpers()
    {
        return $this->_helpers;
    }

    public function unsetHelperFromCache($name)
    {
        unset($this->_helpers[$name]);
    }

}
