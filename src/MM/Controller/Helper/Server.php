<?php
namespace MM\Controller\Helper;

use MM\Controller\Helper;

class Server extends Helper {
	public function __invoke(): Server {
		return $this;
	}

	public function isAjax(): bool {
		return $this->getHeader('X_REQUESTED_WITH') == 'XMLHttpRequest';
	}

	public function getRequestMethod(): string {
		return $this->_controller->params()->_SERVER()->REQUEST_METHOD;
	}

	/**
	 * The POST method is used to submit an entity to the specified resource,
	 * often causing a change in state or side effects on the server
	 */
	public function isPost(): bool {
		return $this->_controller->params()->_SERVER()->REQUEST_METHOD == 'POST';
	}

	/**
	 * The GET method requests a representation of the specified resource.
	 * Requests using GET should only retrieve data.
	 */
	public function isGet(): bool {
		return $this->_controller->params()->_SERVER()->REQUEST_METHOD == 'GET';
	}

	/**
	 * The PUT method replaces all current representations of the target resource
	 * with the request payload.
	 */
	public function isPut(): bool {
		return $this->_controller->params()->_SERVER()->REQUEST_METHOD == 'PUT';
	}

	/**
	 * The DELETE method deletes the specified resource.
	 */
	public function isDelete(): bool {
		return $this->_controller->params()->_SERVER()->REQUEST_METHOD == 'DELETE';
	}

	/**
	 * The PATCH method is used to apply partial modifications to a resource.
	 */
	public function isPatch(): bool {
		return $this->_controller->params()->_SERVER()->REQUEST_METHOD == 'PATCH';
	}

	public function isHttps(): bool {
		// http://php.net/manual/en/reserved.variables.server.php
		$https = strtolower($this->_controller->params()->_SERVER()->HTTPS);

		// Set to a non-empty value if the script was queried through the HTTPS protocol.
		if (empty($https)) {
			return false;
		}

		// Note: Note that when using ISAPI with IIS, the value will be "off" if
		// the request was not made through the HTTPS protocol.
		return 'off' != $https;
	}

	public function remoteIp(): ?string {
		$keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
		foreach ($keys as $key) {
			if ($ip = $this->_controller->params()->_SERVER()->$key) {
				return $ip;
			}
		}
		return null;
	}

	public function getHeader($header) {
		// Try to get it from the $_SERVER array first
		$temp = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
		if ($value = $this->_controller->params()->_SERVER()->$temp) {
			return $value;
		}

		// This seems to be the only way to get the Authorization header on
		// Apache
		if (function_exists('apache_request_headers')) {
			$headers = apache_request_headers();
			if (isset($headers[$header])) {
				return $headers[$header];
			}
			$header = strtolower($header);
			foreach ($headers as $key => $value) {
				if (strtolower($key) == $header) {
					return $value;
				}
			}
		}

		//
		return false;
	}
}
