<?php
namespace MM\Controller\Tests\Controller;

class ObDisabledController extends \MM\Controller\AbstractController {
	protected function _init() {
		$this->setObEnabled(false);
	}

	public function indexAction() {
		echo 'index';
	}

	public function directResponseAction() {
		$this->response()->setBody('response');
	}

	public function staticAction() {
		readfile(__DIR__ . '/output.txt');
	}

	public function throwAction() {
		throw new \MM\Controller\Exception('thrown');
	}
}
