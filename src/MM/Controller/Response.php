<?php
declare(strict_types=1);

namespace MM\Controller;

use DateTimeInterface;
use JetBrains\PhpStorm\Pure;
use MM\Controller\Exception;

class Response implements \ArrayAccess {
	/**
	 * Suchy list podporovanych statusov a ich hlasok. Editovat podla potreby,
	 * ale najskor na to nebude dovod. Prebrate z Zend\Http\Response
	 *
	 * @var array
	 */
	protected static array $_statuses = [
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
	protected array $_body = [];

	/**
	 * @var array
	 */
	protected array $_headers = [];

	/**
	 * Current http status code
	 * @var int
	 */
	protected int $_status = 200;

	// Sets/appends body segment
	public function setBody(
		$value,
		bool $replace = true,
		string $segment = 'default'
	): Response {
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

	public function setHeader(
		string $key,
		string $value,
		bool $replace = true
	): Response {
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
	 */
	public function setCookie(string $name, $value, array $options = []): Response {
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
			return strtolower("$v");
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
			if (preg_match('/\d+/', "$expires")) {
				$expires = date(DateTimeInterface::COOKIE, $expires);
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

	// Interny helper - prejde vsetky cookies a necha len poslednu podla mena
	public function _uniquizeCookies(): Response {
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

	// Navratova hodnota tu mimickuje parametre vyssej setCookie
	public function getCookie($name): ?array {
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
					$options[trim($kv[0])] = $kv[1] ?? null;
				}
				$out[] = $options;
				return $out;
			}
		}
		return null;
	}

	public function getCookies(): array {
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
	 */
	public function unsetCookie(string $name, array $options = []): Response {
		// nizsie (null hodnota) aj trigerne expires v minulosti
		return $this->setCookie($name, null, $options);
	}

	// Debug unfriendly cast to string (body only)
	public function __toString(): string {
		return $this->toString();
	}

	// Debug friendly cast to string (body only)
	public function toString(): string {
		ksort($this->_body);
		return implode('', $this->_body);
	}

	public function getBody(string $key = null, $default = null) {
		if (null == $key) {
			return $this->_body;
		}
		if (isset($this->_body[$key])) {
			return $this->_body[$key];
		}
		return $default;
	}

	public function isBodyEmpty(): bool {
		foreach ($this->_body as $key => $content) {
			if ('' != $content) {
				return false;
			}
		}
		return true;
	}

	public function reset(): Response {
		$this->setStatusCode(200);
		$this->_body = [];
		$this->_headers = [];
		return $this;
	}

	public function getHeader(string $key): ?string {
		$key = $this->_normalizeHeaderKey($key);
		return $this->_headers[$key] ?? null;
	}

	public function getHeaders(): array {
		return $this->_headers;
	}

	public function setStatusCode(int $value): Response {
		if (!isset(self::$_statuses[$value])) {
			throw new Exception("Uknown status '$value'");
		}
		$this->_status = $value;
		return $this;
	}

	public function isValidStatusCode($value): bool {
		return isset(self::$_statuses[$value]);
	}

	public function getStatusCode(): int {
		return $this->_status;
	}

	public function getStatusCodeMessage(): string {
		return self::$_statuses[$this->_status];
	}

	public function getStatusAsString(string $version = '1.1'): string {
		return sprintf(
			"HTTP/$version %d %s",
			$this->getStatusCode(),
			$this->getStatusCodeMessage(),
		);
	}

	// Outputs headers via php's header() function
	public function send(): Response {
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

	// Send headers + echo body
	public function output(): void {
		echo $this->send();
	}

	// Does the status code indicate a client error?
	public function isClientError(): bool {
		$code = $this->getStatusCode();
		return $code < 500 && $code >= 400;
	}

	// Is the request forbidden due to ACLs?
	public function isForbidden(): bool {
		return 403 == $this->getStatusCode();
	}

	// Does the status code indicate the resource is not found?
	public function isNotFound(): bool {
		return 404 === $this->getStatusCode();
	}

	// Do we have a normal, OK response?
	public function isOk(): bool {
		return 200 === $this->getStatusCode();
	}

	// Does the status code reflect a server error?
	public function isServerError(): bool {
		$code = $this->getStatusCode();
		return 500 <= $code && 600 > $code;
	}

	public function isRedirect(): bool {
		$code = $this->getStatusCode();
		return 300 <= $code && 400 > $code;
	}

	// Was the response successful?
	public function isSuccess(): bool {
		$code = $this->getStatusCode();
		return 200 <= $code && 300 > $code;
	}

	protected function _normalizeHeaderKey(string $key): string {
		// "nIeco-ta KE:" => "Nieco-Ta-Ke"
		$key = trim(strtolower($key), ' :');
		$key = ucwords(str_replace('-', ' ', $key));
		return str_replace(' ', '-', $key);
	}

	protected function _normalizeHeaderValue(string $value): string {
		return trim($value);
	}

	public function offsetSet($offset, $value): void {
		$this->setBody($value, true, $offset);
	}

	public function offsetExists($offset): bool {
		return isset($this->_body[$offset]);
	}

	public function offsetUnset($offset): void {
		unset($this->_body[$offset]);
	}

	public function offsetGet($offset): mixed {
		return $this->getBody($offset);
	}

	// sugar
	public function asText(): Response {
		return $this->setHeader('Content-type', 'text/plain; charset=UTF-8');
	}

	// sugar
	public function asJson(): Response {
		return $this->setHeader('Content-type', 'application/json');
	}

	// sugar
	public function asHtml(): Response {
		return $this->setHeader('Content-type', 'text/html; charset=UTF-8');
	}
}
