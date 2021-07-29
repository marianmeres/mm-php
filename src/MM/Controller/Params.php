<?php
/**
 * Obal nad parametrami controllera. Pod parametrami sa tu mysli:
 * _GET, _POST, _SERVER a "custom", kde "custom" maju najvyssiu prioritu.
 *
 * Hlavny zmysel tohoto OOP obalu je, aby sme mohli parametre **referencovat**
 * (napr. do view), co by sme pri beznom poli pochopitelne nemohli a tym padom
 * by sem boli odkazani na "just-in-time" assign parametrov co by mohlo byt
 * nachylne na sklerozu.
 *
 * _GET, _POST, _SERVER defaultuju do php nativnych superglobalov (ale obalenych
 * ako Parameters ArrayObject).
 *
 * _SERVER a _COOKIE su trosku specificke, do neho sa na rozdiel od ostatnych
 * vyssie uvedenych bezne nepozera
 *
 * NOTE: Ano, vnasame trosku schizofreniu do nazvoslovia, kedze tu
 * narabame s tymto klasom "Params" (\ArrayAccess), ktory je de-facto kompozit
 * viacerych jednoduchsich low-level "Parameters" classov (\ArrayObject)
 *
 * NOTE: Niektore metody tu nazyvame nekonvnence (verejne a s "_", navyse uppercase)
 * _GET(), _POST(), _SERVER() aby explicitne odrazali co znamenaju.
 *
 * @todo cookie?
 * @author Marian Meres
 */
namespace MM\Controller;

/**
 * Class Params
 * @package MM\Controller
 */
class Params implements \ArrayAccess
{
	/**
	 * @var Parameters
	 */
	protected $_GET;

	/**
	 * @var Parameters
	 */
	protected $_POST;

	/**
	 * @var Parameters
	 */
	protected $_SERVER;

	/**
	 * @var Parameters
	 */
	protected $_COOKIE;

	/**
	 * @var Parameters
	 */
	protected $_params;

	/**
	 * @param array $options
	 */
	public function __construct(array $options = [])
	{
		$params = [];

		// convenience na pokrytie 99% use case-ov: bezne pole chapem ako "params"
		// situaciu kde by to teoreticky mohlo kolidovat, treba vyriesit manualne
		if (
			!empty($options) &&
			!isset($options['_GET']) &&
			!isset($options['_POST']) &&
			!isset($options['_SERVER']) &&
			!isset($options['_COOKIE']) &&
			!isset($options['params'])
		) {
			$params = $options;
		}

		if (isset($options['params'])) {
			$params = $options['params'];
			unset($options['params']);
		}

		// @note: vytvarat istanciu v constructore sa pravom povazuje za
		// anti-pattern, lebo ide o nepriestrelnu dependency (neda sa nijak
		// injectnut/mocknut) ale tu to beriem ako vynimku potvrdzujucu pravidlo
		// kedze object "Parameters" chapem ako velmi nizky low-level
		$this->_params = new Parameters($params);

		foreach ($options as $k => $data) {
			if (preg_match("/^_(GET|POST|SERVER|COOKIE)$/", $k)) {
				$this->setInternal($k, $data);
			}
		}
	}

	/**
	 * Nullne vsetky interne kontajnre. Vhodne pri testoch.
	 * @return $this
	 */
	public function reset()
	{
		$this->_GET = null;
		$this->_POST = null;
		$this->_SERVER = null;
		$this->_COOKIE = null;
		$this->_params->exchangeArray([]);
		return $this;
	}

	/**
	 * Main API. Skusi najst a vratit parameter. Postupuje podla poradia priority.
	 *
	 * @param null $key
	 * @param null $default
	 * @return array|null
	 */
	public function get($key = null, $default = null)
	{
		// ak key je null, tak vraciame vsetko mergnute spolu (podla priority)
		if (null === $key) {
			return $this->toArray();
		}

		// 1. internal params container?
		//if (isset($this->_params[$key])) {
		// vyssii isset je problematicky pri unset/isset
		if (property_exists($this->_params, $key)) {
			return $this->_params[$key];
		}

		// 2. _GET
		if (isset($this->_GET()->$key)) {
			return $this->_GET()->$key;
		}

		// 3. _POST
		if (isset($this->_POST()->$key)) {
			return $this->_POST()->$key;
		}

		// 4. not found above, so default
		return $default;
	}

	/**
	 * Vrati vsetko mergnute podla priority
	 * @return array
	 */
	public function toArray()
	{
		return array_merge(
			$this->_POST()->getArrayCopy(),
			$this->_GET()->getArrayCopy(),
			$this->_params->getArrayCopy()
		);
	}

	/**
	 * Main API. Un/setne hodnotu (alebo vsetko) do interneho "_params" kontainera.
	 * Tato setnuta hodnota bude mat prioritu nad _GET a _POST.
	 *
	 * @param $dataOrKey
	 * @param null $value
	 * @return $this
	 */
	public function set($dataOrKey, $value = null)
	{
		if (is_array($dataOrKey)) {
			$this->_params->exchangeArray($dataOrKey);
		} elseif (null === $value && isset($this->_params[$dataOrKey])) {
			unset($this->_params[$dataOrKey]);
		} elseif (null !== $value) {
			$this->_params[$dataOrKey] = $value;
		}

		return $this;
	}

	/**
	 * Magic accessor. Main API.
	 * @param $name
	 * @return array|null
	 */
	public function __get($name)
	{
		return $this->get($name);
	}

	/**
	 * Magic mutator. Main API.
	 * @param $name
	 * @param $value
	 * @return $this
	 */
	public function __set($name, $value)
	{
		return $this->set($name, $value);
	}

	/**
	 * @param $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return $this->get($name, null) !== null;
	}

	/**
	 * @param $name
	 */
	public function __unset($name)
	{
		// do params kontajnera (s najvyssou prioritou) explictneme setneme null
		// isset/get budu takto fungovat korektne, bez toho aby sme museli
		// sahat do _GET a _POST
		$this->_params[$name] = null;
	}

	/**
	 * Nizsie su metodky primarne urcene na testing/hacking. Mozno povazovat
	 * mimo bezneho API.
	 */

	/**
	 * @note Umyselne nekonvencny nazov
	 * @return \MM\Controller\Parameters
	 */
	public function _GET()
	{
		if (null === $this->_GET) {
			$this->_GET = new Parameters($_GET); // defaults to php's superglobal
		}
		return $this->_GET;
	}

	/**
	 * @note Umyselne nekonvencny nazov
	 * @return \MM\Controller\Parameters
	 */
	public function _POST()
	{
		if (null === $this->_POST) {
			$this->_POST = new Parameters($_POST); // defaults to php's superglobal
		}
		return $this->_POST;
	}

	/**
	 * @note Umyselne nekonvencny nazov
	 * @return \MM\Controller\Parameters
	 */
	public function _SERVER()
	{
		if (null === $this->_SERVER) {
			$this->_SERVER = new Parameters($_SERVER); // defaults to php's superglobal
		}
		return $this->_SERVER;
	}

	/**
	 * @note Umyselne nekonvencny nazov
	 * @return \MM\Controller\Parameters
	 */
	public function _COOKIE()
	{
		if (null === $this->_COOKIE) {
			$this->_COOKIE = new Parameters($_COOKIE); // defaults to php's superglobal
		}
		return $this->_COOKIE;
	}

	/**
	 * Un/Setne hodnotu do "_*" kontainerov. Mimo testov nie je dovod volat.
	 *
	 * @param $which
	 * @param $dataOrKey
	 * @param null $value
	 * @return $this
	 * @throws \MM\Controller\Exception
	 */
	public function setInternal($which, $dataOrKey, $value = null)
	{
		if (!preg_match("/^_(GET|POST|SERVER|COOKIE)$/", $which)) {
			throw new Exception("Invalid parameter '$which'");
		}

		// ak posielame pole, tak reset a return early
		if (is_array($dataOrKey)) {
			$this->$which = new Parameters($dataOrKey);
			return $this;
		}

		// toto vynuti typ Parameters s defaultnym obsahom ak este neexistuje
		$container = $this->$which;

		if (null === $value) {
			unset($container[$dataOrKey]);
		} else {
			$container[$dataOrKey] = $value;
		}

		return $this;
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @return $this|void
	 */
	public function offsetSet($offset, $value)
	{
		return $this->set($offset, $value);
	}

	/**
	 * @see \ArrayAccess
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		// return isset($this->_params[$offset]);

		// @mm nizsie myslim viac odpoveda zmyslu
		$p = $this->toArray();
		return isset($p[$offset]);
	}

	/**
	 * @see \ArrayAccess
	 * @param mixed $offset
	 */
	public function offsetUnset($offset)
	{
		unset($this->_params[$offset]);
	}

	/**
	 * @see \ArrayAccess
	 * @param mixed $offset
	 * @return array|mixed|null
	 */
	public function offsetGet($offset)
	{
		return $this->get($offset);
	}
}
