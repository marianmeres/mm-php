<?php

namespace MM\View;

abstract class ViewAbstract {
	private array $_vars = [];

	/**
	 * helper instances
	 */
	protected array $_helpers = [];

	private string $_templateDir = '';

	/**
	 * Auto escape vars when overloading via magic __get?
	 */
	private bool $_autoEscape = true;

	/**
	 * Trigger notices when accessing undefined view vars?
	 */
	private bool $_strictVars = false;

	public function __construct(array $options = null) {
		if ($options) {
			$this->setOptions($options);
		}

		$this->_init();
	}

	/**
	 * Init hook
	 */
	protected function _init() {
	}

	public function setStrictVars($flag): static {
		$this->_strictVars = (bool) $flag;
		return $this;
	}

	public function setAutoEscape($flag): static {
		$this->_autoEscape = (bool) $flag;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getAutoEscape() {
		return $this->_autoEscape;
	}

	public function setTemplateDir($dir): static {
		$this->_templateDir = $dir;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTemplateDir() {
		return $this->_templateDir;
	}

	/**
	 * Sets options which have normalized setter. Otherwise throws.
	 */
	public function setOptions(array $options): static {
		foreach ($options as $_key => $value) {
			$key = str_replace('_', ' ', strtolower(trim($_key))); // under_scored to CamelCase
			$key = str_replace(' ', '', ucwords($key));
			$method = "set$key";
			if (!method_exists($this, $method)) {
				throw new Exception("Unknown view option '$_key'");
			}
			$this->$method($value);
		}
		return $this;
	}

	public function setVars(array $vars, $merge = false): static {
		$this->_vars = $merge ? array_merge($this->_vars, $vars) : $vars;
		return $this;
	}

	/**
	 * Get raw value
	 */
	public function raw($key): mixed {
		if (null === $key) {
			return $this->_vars;
		}
		return $this->_vars[$key];
	}

	/**
	 * more intention friendly alias
	 */
	public function html($key) {
		return $this->raw($key);
	}

	/**
	 * @return array
	 */
	public function dump() {
		return $this->_vars;
	}

	public function __isset(string $name): bool {
		return array_key_exists($name, $this->_vars);
	}

	public function __unset(string $name) {
		if ($this->__isset($name)) {
			unset($this->_vars[$name]);
		}
	}

	public function __get(string $name): mixed {
		if ($this->__isset($name)) {
			if (!$this->_autoEscape || !is_scalar($this->_vars[$name])) {
				return $this->_vars[$name];
			}

			// defaultny escape
			return $this->htmlspecialchars($this->_vars[$name]);
		}

		if (!$this->_strictVars) {
			return null;
		}

		trigger_error("Undefined view var '$name'", E_USER_NOTICE);
	}

	public function __set($name, $value): void {
		$this->_vars[$name] = $value;
	}

	// /**
	//  * @param $name
	//  * @param $arguments
	//  * @throws Exception
	//  */
	// public function __call($name, $arguments)
	// {
	//     throw new Exception(
	//         "Missing view method '$name'; Hint: magic helpers were removed"
	//     );
	// }

	// protected function _normalizeHelperName($name)
	// {
	//     return strtolower($name);
	// }

	// /**
	//  * @param $name
	//  * @param null $fqnOrInstance
	//  * @param bool $reset
	//  * @return $this
	//  * @throws Exception
	//  */
	// public function setHelper($name, $fqnOrInstance = null, $reset = false)
	// {
	//     $name = $this->_normalizeHelperName($name);

	//     //
	//     if ($reset) {
	//         unset($this->_helpers[$name]);
	//     }
	//     // ak neresetujeme a uz nieco existuje, tak return early, pouzijeme to co mame
	//     elseif (isset($this->_helpers[$name])) {
	//         return $this;
	//     }

	//     // ak posielame null, tak return early nic
	//     if (null === $fqnOrInstance) {
	//         return $this;
	//     }

	//     // povolujeme string alebo uz hotovu Helper instanciu
	//     if (is_string($fqnOrInstance) || $fqnOrInstance instanceof Helper) {
	//         $this->_helpers[$name] = $fqnOrInstance;
	//         return $this;
	//     }

	//     // ak sme tu tak neznamy typ
	//     throw new Exception(
	//         "Invalid view helper. Expecting either fully qualified "
	//         . "class name or actual Helper instance"
	//     );
	// }

	// /**
	//  * @param array $nameToFqn
	//  * @throws Exception
	//  */
	// public function setHelpers(array $nameToFqn)
	// {
	//     foreach ($nameToFqn as $name => $fqn) {
	//         $this->setHelper($name, $fqn);
	//     }
	// }

	// /**
	//  * @param $name
	//  * @param null $fallbackFqnOrInstance
	//  * @return null
	//  * @throws Exception
	//  */
	// public function getHelper($name, $fallbackFqnOrInstance = null)
	// {
	//     $name = $this->_normalizeHelperName($name);

	//     //
	//     if (!isset($this->_helpers[$name])) {
	//         // use fallback if provided
	//         if ($fallbackFqnOrInstance) {
	//             $this->setHelper($name, $fallbackFqnOrInstance);
	//         } else {
	//             return null;
	//         }
	//     }

	//     // JIT, lazy, string to instance
	//     if (is_string($this->_helpers[$name])) {
	//         $helper = new $this->_helpers[$name]($this);
	//         // note1: use setter because it validates
	//         // note2: important $reset=true (3rd param)
	//         $this->setHelper($name, $helper, true);
	//     }

	//     //
	//     return $this->_helpers[$name];
	// }

	// /**
	//  * @param $name
	//  * @return bool
	//  */
	// public function hasHelper($name)
	// {
	//     $name = $this->_normalizeHelperName($name);
	//     return isset($this->_helpers[$name]);
	// }

	/**
	 * built-in shortcut
	 */
	public function htmlspecialchars($val): string {
		return htmlspecialchars($val, ENT_COMPAT, 'UTF-8');
	}

	/**
	 * Renders a view template/script under template dir
	 */
	public function render($template, array $vars = [], $ext = '.phtml'): string {
		return $this->renderScript($this->_templateDir . $template, $vars, $ext);
	}

	/**
	 * Renders a view template/script absolute filename
	 */
	public function renderScript($template, array $vars = [], $ext = '.phtml'): string {
		// add extension if not exists
		if ($ext && $ext !== strtolower(substr($template, -strlen($ext)))) {
			$template .= $ext;
		}

		$this->setVars($vars, true);

		// exception vypluvame na cistej luke
		ob_start();
		try {
			$this->_run($template);
			return ob_get_clean();
		} catch (\Exception $e) {
			ob_end_clean();
			throw $e;
		}
	}

	/**
	 * @return mixed
	 */
	abstract protected function _run();
}
