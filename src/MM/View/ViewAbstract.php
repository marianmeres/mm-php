<?php
/**
 * @author Marian Meres
 */
namespace MM\View;

/**
 * Class ViewAbstract
 * @package MM\View
 */
abstract class ViewAbstract
{
	/**
	 * @var array
	 */
	private $_vars = [];

	/**
	 * helper instances
	 * @var array
	 */
	// private $_helpers = array();

	/**
	 * Out-of-the-box "Two step view" design pattern
	 * @var View
	 */
	private $_view;

	/**
	 * @var string
	 */
	private $_templateDir;

	/**
	 * Auto escape vars when overloading via magic __get?
	 * @var bool
	 */
	private $_autoEscape = true;

	/**
	 * Trigger notices when accessing undefined view vars?
	 * @var bool
	 */
	private $_strictVars = true;

	/**
	 * @param array $options
	 * @throws Exception
	 */
	public function __construct(array $options = null)
	{
		if ($options) {
			$this->setOptions($options);
		}

		$this->_init();
	}

	/**
	 * Init hook
	 */
	protected function _init()
	{
	}

	/**
	 * @param $flag
	 * @return $this
	 */
	public function setStrictVars($flag)
	{
		$this->_strictVars = (bool) $flag;
		return $this;
	}

	/**
	 * @param $flag
	 * @return $this
	 */
	public function setAutoEscape($flag)
	{
		$this->_autoEscape = (bool) $flag;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getAutoEscape()
	{
		return $this->_autoEscape;
	}

	/**
	 * @param View $view
	 * @return $this
	 */
	public function setView(View $view = null)
	{
		$this->_view = $view;
		return $this;
	}

	/**
	 * @return View
	 */
	public function view()
	{
		return $this->_view;
	}

	/**
	 * @param $dir
	 * @return $this
	 */
	public function setTemplateDir($dir)
	{
		$this->_templateDir = $dir;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTemplateDir()
	{
		return $this->_templateDir;
	}

	/**
	 * Sets options which have normalized setter. Otherwise throws.
	 * @param array $options
	 * @return $this
	 * @throws Exception
	 */
	public function setOptions(array $options)
	{
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

	/**
	 * @param array $vars
	 * @return $this
	 */
	public function setVars(array $vars)
	{
		$this->_vars = $vars;
		return $this;
	}

	/**
	 * Get raw value
	 * @param $key
	 * @return mixed
	 */
	public function raw($key)
	{
		if (null === $key) {
			return $this->_vars;
		}
		return $this->_vars[$key];
	}

	/**
	 * @return array
	 */
	public function dump()
	{
		return $this->_vars;
	}

	/**
	 * @param $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return array_key_exists($name, $this->_vars);
	}

	/**
	 * @param $name
	 */
	public function __unset($name)
	{
		if ($this->__isset($name)) {
			unset($this->_vars[$name]);
		}
	}

	/**
	 * @param $name
	 * @return null|string
	 */
	public function __get($name)
	{
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

	/**
	 * @param $name
	 * @param $value
	 * @return $this
	 */
	public function __set($name, $value)
	{
		$this->_vars[$name] = $value;
		return $this;
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

	/**
	 * @param $name
	 * @return string
	 */
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
	 * @param $val
	 * @return string
	 */
	public function htmlspecialchars($val)
	{
		return htmlspecialchars($val, ENT_COMPAT, 'UTF-8');
	}

	/**
	 * Renders a view template/script under template dir
	 *
	 * @param $template
	 * @return string
	 */
	public function render($template)
	{
		return $this->renderScript($this->_templateDir . $template);
	}

	/**
	 * Renders a view template/script absolute filename
	 *
	 * @param $template
	 * @return string
	 * @throws \Exception
	 */
	public function renderScript($template)
	{
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
