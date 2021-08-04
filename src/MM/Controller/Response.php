<?php
namespace MM\Controller;

use MM\Controller\Exception;

/**
 * Class Response
 * @package MM\Controller
 */
class Response implements \ArrayAccess {
	/**
	 * Suchy list podporovanych statusov a ich hlasok. Editovat podla potreby,
	 * ale najskor na to nebude dovod. Prebrate z Zend\Http\Response
	 *
	 * @var array
	 */
	protected static $_statuses = [
		// INFORMATIONAL CODES
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		// SUCCESS CODES
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-status',
		208 => 'Already Reported',
		// REDIRECTION CODES
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => 'Switch Proxy', // Deprecated
		307 => 'Temporary Redirect',
		// CLIENT ERROR
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Time-out',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Large',
		415 => 'Unsupported Media Type',
		416 => 'Requested range not satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		425 => 'Unordered Collection',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		// SERVER ERROR
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Time-out',
		505 => 'HTTP Version not supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		511 => 'Network Authentication Required',
	];

	/**
	 * Telo responsu, rozdelene na segmenty. Defaultny segment sa vola "default"
	 * @var array
	 */
	protected $_body = [];

	/**
	 * @var array
	 */
	protected $_headers = [];

	/**
	 * Current http status code
	 * @var int
	 */
	protected $_status = 200;

	/**
	 * Sets/appends body segment
	 *
	 * @param string $value
	 * @param bool $replace
	 * @param string $segment
	 * @return Response
	 */
	public function setBody($value, $replace = true, $segment = 'default') {
		// ak je value array, tak reset celeho body
		if (is_array($value)) {
			$this->_body = $value;
			return $this;
		}

		// set segment
		if ($replace) {
			$this->_body[$segment] = $value;
		}
		// append to segment
		else {
			if (!isset($this->_body[$segment])) {
				$this->_body[$segment] = '';
			}
			$this->_body[$segment] .= $value;
		}

		return $this;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $replace
	 * @return $this
	 */
	public function setHeader($key, $value, $replace = true) {
		$key = $this->_normalizeHeaderKey($key);
		$value = $this->_normalizeHeaderValue($value);

		if ($replace || !array_key_exists($key, $this->_headers)) {
			$this->_headers[$key] = $value;
			return $this;
		}

		// force array ak este nie je
		if (!is_array($this->_headers[$key])) {
			$this->_headers[$key] = [$this->_headers[$key]];
		}

		array_push($this->_headers[$key], $value);
		return $this;
	}

	/**
	 * Cookie je normalna http "Set-Cookie" hlavicka... ale pre pohodlie
	 * si to tu trosku obalime.
	 *
	 * DISCLAIMER: ziadnu RFC cookie specifikaciu som nikdy necital... jedine
	 * toto: http://en.wikipedia.org/wiki/HTTP_cookie
	 *
	 * @param $name
	 * @param $value
	 * @param array $options
	 * @return $this
	 * @throws Exception
	 */
	public function setCookie($name, $value, array $options = []) {
		$name = trim($name);

		// sanity check
		if ('' == $name) {
			throw new Exception('Cookie name must not be empty');
		}

		// feature: ak je value null, tak to chapeme ako unset
		if (null === $value) {
			// When deleting a cookie you should assure that the expiration
			// date is in the past, to trigger the removal mechanism in your
			// browser.
			$options['expires'] = time() - 60 * 60 * 24 * 30;
		}

		// na poradi parov okrem prveho najskor nazalezi... ale kedze som
		// niecital ziadne RFC postupujem tak ako je uvedene na wiki
		$pairs = ["$name=$value"];

		array_walk($options, function ($v, $k) {
			return strtolower($v);
		});

		if (!empty($options['domain'])) {
			$pairs[] = 'domain=' . $options['domain'];
		}

		if (!empty($options['path'])) {
			$pairs[] = 'path=' . preg_replace('~//+~', '/', "/$options[path]/");
		}

		// notaciu "duration" chapem ako alias k "lifetime", ale ak existuje
		// aj "lifetime", tak ten ma vyssiu prioritu
		if (
			array_key_exists('duration', $options) &&
			!array_key_exists('lifetime', $options)
		) {
			$options['lifetime'] = $options['duration'];
			unset($options['duration']);
		}

		// pozname "expires" aj vlastny "duration" (co je de facto max-age
		// s expires notaciou)
		if (!empty($options['expires'])) {
			$expires = $options['expires'];
		} elseif (!empty($options['lifetime'])) {
			$expires = time() + (int) $options['lifetime'];
		}
		if (!empty($expires)) {
			if (preg_match('/\d+/', $expires)) {
				$expires = date(\DateTime::COOKIE, $expires);
			}
			$pairs[] = "expires=$expires";
		}

		foreach (['secure', 'httponly'] as $_key) {
			if (!empty($options[$_key])) {
				$pairs[] = $_key;
			}
		}

		// tu je dolezity replace false, lebo header name je tu vzdy rovnaky
		$this->setHeader('Set-Cookie', implode('; ', $pairs), $replace = false);

		// toto sem davame ako taku pragmaticku konvenience... dizajnovo to sem
		// ale vobec nepatri...
		// problem co tu vidim je, ze tu setovanie _COOKIE sa mi uz neprejavi
		// v MVC params napr... lebo to nie je referencia
		if (null === $value) {
			unset($_COOKIE[$name]);
		} else {
			$_COOKIE[$name] = $value;
		}

		// aby bol poriadok
		$this->_uniquizeCookies();

		return $this;
	}

	/**
	 * Interny helper - prejde vsetky cookies a necha len poslednu podla mena
	 *
	 * @return $this
	 */
	public function _uniquizeCookies() {
		$unique = [];
		$cookieHdr = $this->_normalizeHeaderKey('Set-Cookie');
		if (!empty($this->_headers[$cookieHdr])) {
			foreach ((array) $this->_headers[$cookieHdr] as $str) {
				$name = trim(substr($str, 0, strpos($str, '=')));
				$unique[$name] = $str; //neskorsi vyhrava
			}
			$this->_headers[$cookieHdr] = array_values($unique);
		}
		return $this;
	}

	/**
	 * Navratova hodnota tu mimickuje parametre vyssej setCookie
	 *
	 * @param $name
	 * @return array|null
	 */
	public function getCookie($name) {
		$cookies = $this->getCookies();

		foreach ($cookies as $hdrString) {
			$pairs = explode(';', $hdrString);
			$kv = explode('=', trim($pairs[0]));
			if ($name == trim($kv[0])) {
				$out = [$name, $kv[1]];
				unset($pairs[0]);

				$options = [];
				foreach ($pairs as $pair) {
					$kv = explode('=', trim($pair));
					$options[trim($kv[0])] = isset($kv[1]) ? $kv[1] : null;
				}
				$out[] = $options;
				return $out;
			}
		}
		return null;
	}

	/**
	 * @return array
	 */
	public function getCookies() {
		$cookieHdr = $this->_normalizeHeaderKey('Set-Cookie');
		$cookies = [];
		if (!empty($this->_headers[$cookieHdr])) {
			$cookies = (array) $this->_headers[$cookieHdr];
		}
		return $cookies;
	}

	/**
	 * When deleting a cookie you should assure that the expiration date is in
	 * the past, to trigger the removal mechanism in your browser.
	 *
	 * @param $name
	 * @param array $options
	 * @return $this
	 */
	public function unsetCookie($name, array $options = []) {
		// nizsie (null hodnota) aj trigerne expires v minulosti
		return $this->setCookie($name, null, $options);
	}

	/**
	 * Debug unfriendly cast to string (body only)
	 * @return string
	 */
	public function __toString() {
		return $this->toString();
	}

	/**
	 * Debug friendly cast to string (body only)
	 * @return string
	 */
	public function toString() {
		ksort($this->_body);
		return implode('', $this->_body);
	}

	/**
	 * @param null $key
	 * @param null $default
	 * @return array|null
	 */
	public function getBody($key = null, $default = null) {
		if (null == $key) {
			return $this->_body;
		}
		if (isset($this->_body[$key])) {
			return $this->_body[$key];
		}
		return $default;
	}

	/**
	 * @return bool
	 */
	public function isBodyEmpty() {
		foreach ($this->_body as $key => $content) {
			if ('' != $content) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @return Response
	 */
	public function reset() {
		$this->setStatusCode(200);
		$this->_body = [];
		$this->_headers = [];
		return $this;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getHeader($key) {
		$key = $this->_normalizeHeaderKey($key);
		return isset($this->_headers[$key]) ? $this->_headers[$key] : null;
	}

	/**
	 * @return array
	 */
	public function getHeaders() {
		return $this->_headers;
	}

	/**
	 * @param $value
	 * @return $this
	 * @throws Exception
	 */
	public function setStatusCode($value) {
		if (!isset(self::$_statuses[$value])) {
			throw new Exception("Uknown status '$value'");
		}
		$this->_status = $value;
		return $this;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function isValidStatusCode($value) {
		return isset(self::$_statuses[$value]);
	}

	/**
	 * @return int
	 */
	public function getStatusCode() {
		return $this->_status;
	}

	/**
	 * @return string
	 */
	public function getStatusCodeMessage() {
		return self::$_statuses[$this->_status];
	}

	/**
	 * @param string $version
	 * @return string
	 */
	public function getStatusAsString($version = '1.1') {
		return sprintf(
			"HTTP/$version %d %s",
			$this->getStatusCode(),
			$this->getStatusCodeMessage(),
		);
	}

	/**
	 * Output headers via php's header() function
	 * @return $this
	 */
	public function send() {
		// ma zmysel vypluvat aj OK?
		if (200 !== $this->getStatusCode()) {
			header($this->getStatusAsString());
		}

		// others next
		foreach ($this->_headers as $key => $value) {
			// value moze by aj array ak bolo setnute s "$replace=true"
			// typicky platie pre "Set-Cookie" header
			if (is_array($value)) {
				foreach ($value as $_value) {
					header("$key: $_value", $replace = false);
				}
			} else {
				header("$key: $value");
			}
		}

		return $this;
	}

	/**
	 * Posle hlavicky a echne telo
	 */
	public function output() {
		echo $this->send();
	}

	/**
	 * Does the status code indicate a client error?
	 * @return bool
	 */
	public function isClientError() {
		$code = $this->getStatusCode();
		return $code < 500 && $code >= 400;
	}

	/**
	 * Is the request forbidden due to ACLs?
	 * @return bool
	 */
	public function isForbidden() {
		return 403 == $this->getStatusCode();
	}

	/**
	 * Does the status code indicate the resource is not found?
	 * @return bool
	 */
	public function isNotFound() {
		return 404 === $this->getStatusCode();
	}

	/**
	 * Do we have a normal, OK response?
	 * @return bool
	 */
	public function isOk() {
		return 200 === $this->getStatusCode();
	}

	/**
	 * Does the status code reflect a server error?
	 * @return bool
	 */
	public function isServerError() {
		$code = $this->getStatusCode();
		return 500 <= $code && 600 > $code;
	}

	/**
	 * Do we have a redirect?
	 * @return bool
	 */
	public function isRedirect() {
		$code = $this->getStatusCode();
		return 300 <= $code && 400 > $code;
	}

	/**
	 * Was the response successful?
	 * @return bool
	 */
	public function isSuccess() {
		$code = $this->getStatusCode();
		return 200 <= $code && 300 > $code;
	}

	/**
	 * @param $key
	 * @return string
	 */
	protected function _normalizeHeaderKey($key) {
		// "nIeco-ta KE:" => "Nieco-Ta-Ke"
		$key = trim(strtolower($key), ' :');
		$key = ucwords(str_replace('-', ' ', $key));
		$key = str_replace(' ', '-', $key);
		return $key;
	}

	/**
	 * @param $value
	 * @return string
	 */
	protected function _normalizeHeaderValue($value) {
		return trim($value);
	}

	/**
	 * @see \ArrayAccess
	 * @param $offset
	 * @param $value
	 */
	public function offsetSet($offset, $value) {
		$this->setBody($value, true, $offset);
	}

	/**
	 * @see \ArrayAccess
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
		return isset($this->_body[$offset]);
	}

	/**
	 * @see \ArrayAccess
	 * @param mixed $offset
	 */
	public function offsetUnset($offset) {
		unset($this->_body[$offset]);
	}

	/**
	 * @see \ArrayAccess
	 * @param mixed $offset
	 * @return array|mixed|null
	 */
	public function offsetGet($offset) {
		return $this->getBody($offset);
	}

	/**
	 * sugar
	 */
	public function asText() {
		return $this->setHeader('Content-type', 'text/plain; charset=UTF-8');
	}

	/**
	 * sugar
	 */
	public function asJson() {
		return $this->setHeader('Content-type', 'application/json');
	}

	/**
	 * sugar
	 */
	public function asHtml() {
		return $this->setHeader('Content-type', 'text/html; charset=UTF-8');
	}
}
