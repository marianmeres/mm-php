<?php
namespace MM\Controller\Helper;

use MM\Controller\Helper;

class Server extends Helper
{
	/**
	 * @return $this
	 */
	public function __invoke()
	{
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isAjax()
	{
		return $this->getHeader('X_REQUESTED_WITH') == 'XMLHttpRequest';
	}

	/**
	 * @return mixed
	 */
	public function getRequestMethod()
	{
		return $this->_controller->params()->_SERVER()->REQUEST_METHOD;
	}

	/**
	 * The POST method is used to submit an entity to the specified resource, often causing a change in state or side effects on the server
	 * @return bool
	 */
	public function isPost()
	{
		return $this->_controller->params()->_SERVER()->REQUEST_METHOD == 'POST';
	}

	/**
	 * The GET method requests a representation of the specified resource. Requests using GET should only retrieve data.
	 * @return bool
	 */
	public function isGet()
	{
		return $this->_controller->params()->_SERVER()->REQUEST_METHOD == 'GET';
	}

	/**
	 * The PUT method replaces all current representations of the target resource with the request payload.
	 * @return bool
	 */
	public function isPut()
	{
		return $this->_controller->params()->_SERVER()->REQUEST_METHOD == 'PUT';
	}

	/**
	 * The DELETE method deletes the specified resource.
	 * @return bool
	 */
	public function isDelete()
	{
		return $this->_controller->params()->_SERVER()->REQUEST_METHOD == 'DELETE';
	}

	/**
	 * The PATCH method is used to apply partial modifications to a resource.
	 * @return bool
	 */
	public function isPatch()
	{
		return $this->_controller->params()->_SERVER()->REQUEST_METHOD == 'PATCH';
	}

	/**
	 * @return bool
	 */
	public function isHttps()
	{
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

	/**
	 * @return string|null
	 */
	public function remoteIp()
	{
		$keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
		foreach ($keys as $key) {
			if ($ip = $this->_controller->params()->_SERVER()->$key) {
				return $ip;
			}
		}
		return null;
	}

	/**
	 * @param $header
	 * @return bool
	 */
	public function getHeader($header)
	{
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
