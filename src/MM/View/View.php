<?php

namespace MM\View;

use MM\View\Helper\BodyTagClass;
use MM\View\Helper\Breadcrumbs;
use MM\View\Helper\Canonicalize;
use MM\View\Helper\ContainerOfStrings;
use MM\View\Helper\HeadCss;
use MM\View\Helper\HeadCssSrc;
use MM\View\Helper\HeadCssSrcNonBlocking;
use MM\View\Helper\HeadScript;
use MM\View\Helper\HeadScriptSrc;
use MM\View\Helper\HeadTitle;
use MM\View\Helper\HtmlBaseTag;
use MM\View\Helper\HtmlTagClass;
use MM\View\Helper\LinkRel;
use MM\View\Helper\LinkRelCanonical;
use MM\View\Helper\LinkRelNext;
use MM\View\Helper\LinkRelPrev;
use MM\View\Helper\MetaNameTags;
use MM\View\Helper\OpenGraphData;

class View extends ViewAbstract {
	public mixed $__scriptIncludeReturn;

	/**
	 * Trick to make clean scoped template. Taken from ZF1.
	 */
	protected function _run(): void {
		// http://php.net/manual/en/function.include.php
		// ... It is possible to execute a return statement inside an included file
		// in order to terminate processing in that file and return to the script
		// which called it. Also, it's possible to return values from included files.
		// You can take the value of the include call as you would for a normal
		// function. ...
		//
		// Important here is the actual include... return value is saved only
		// for special cases, should they ever be needed
		$this->__scriptIncludeReturn = include func_get_arg(0);
	}

	protected function _factoryHelper(string $name, callable $factory, $reset = false) {
		$name = strtolower($name);
		if (!isset($this->_helpers[$name]) || $reset) {
			$this->_helpers[$name] = $factory($this);
		}
		return $this->_helpers[$name];
	}

	public function headTitle($strings = null, $method = 'append', $escape = null) {
		return $this->_factoryHelper('headTitle', function (View $view) {
			return new HeadTitle($view);
		})->__invoke($strings, $method, $escape);
	}

	public function headScriptSrc($strings = null, $method = 'append', $escape = null) {
		return $this->_factoryHelper('headScriptSrc', function (View $view) {
			return new HeadScriptSrc($view);
		})->__invoke($strings, $method, $escape);
	}

	public function headScript($strings = null, $method = 'append', $escape = null) {
		return $this->_factoryHelper('headScript', function (View $view) {
			return new HeadScript($view);
		})->__invoke($strings, $method, $escape);
	}

	public function headCssSrc($strings = null, $method = 'append', $escape = null) {
		return $this->_factoryHelper('headCssSrc', function (View $view) {
			return new HeadCssSrc($view);
		})->__invoke($strings, $method, $escape);
	}

	public function headCssSrcNonBlocking(
		$strings = null,
		$method = 'append',
		$escape = null
	) {
		return $this->_factoryHelper('headCssSrcNonBlocking', function (View $view) {
			return new HeadCssSrcNonBlocking($view);
		})->__invoke($strings, $method, $escape);
	}

	public function headCss($strings = null, $method = 'append', $escape = null) {
		return $this->_factoryHelper('headCss', function (View $view) {
			return new HeadCss($view);
		})->__invoke($strings, $method, $escape);
	}

	public function breadcrumbs() {
		return $this->_factoryHelper('breadcrumbs', function (View $view) {
			return new Breadcrumbs($view);
		});
	}

	public function htmlTagClass($strings = null) {
		return $this->_factoryHelper('htmlTagClass', function (View $view) {
			return new HtmlTagClass($view);
		})->__invoke($strings, 'append', null);
	}

	public function bodyTagClass($strings = null) {
		return $this->_factoryHelper('htmlTagClass', function (View $view) {
			return new BodyTagClass($view);
		})->__invoke($strings, 'append', null);
	}

	public function htmlBaseTag($href = null, $target = null) {
		return $this->_factoryHelper('htmlBaseTag', function (View $view) {
			return new HtmlBaseTag($view);
		})->__invoke($href, $target);
	}

	public function linkRelCanonical($href = null) {
		return $this->_factoryHelper('linkRelCanonical', function (View $view) {
			return new LinkRelCanonical($view);
		})->__invoke($href);
	}

	public function linkRelPrev($href = null) {
		return $this->_factoryHelper('linkRelPrev', function (View $view) {
			return new LinkRelPrev($view);
		})->__invoke($href);
	}

	public function linkRelNext($href = null) {
		return $this->_factoryHelper('linkRelNext', function (View $view) {
			return new LinkRelNext($view);
		})->__invoke($href);
	}

	public function linkRel() {
		return $this->_factoryHelper('linkRel', function (View $view) {
			return new LinkRel($view);
		});
	}

	public function metaNameTags($name = null, $content = null) {
		$helper = $this->_factoryHelper('metaNameTags', function (View $view) {
			return new MetaNameTags($view);
		});
		if ($name) {
			$helper->set($name, $content);
		}
		return $helper;
	}

	public function openGraphData(
		$propertyOrData = null,
		$content = null,
		$overwrite = true
	) {
		$helper = $this->_factoryHelper('openGraphData', function (View $view) {
			return new OpenGraphData($view);
		});
		if ($propertyOrData) {
			$helper->add($propertyOrData, $content, $overwrite);
		}
		return $helper;
	}

	public function canonicalize($url = null) {
		$helper = $this->_factoryHelper('canonicalize', function (View $view) {
			return new Canonicalize($view);
		});
		return $url ? $helper->__invoke($url) : $helper;
	}

	public function cssClassFor($label, $classes = null) {
		return $this->_factoryHelper('cssClassFor', function (View $view) {
			return new ContainerOfStrings($view);
		})->__invoke($label, $classes);
	}
}
