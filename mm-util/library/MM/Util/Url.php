<?php
/**
 * @author Marian Meres
 */
namespace MM\Util;

class Url
{
    /**
     * Trosku normalizuje chovanie parse_url
     *
     * @param $url
     * @param null $component
     * @return array
     */
    public static function parse($url, $component = null)
    {
        $urlParts = array_merge(
            array(
                'scheme' => '', 'user' => '', 'pass' => '', 'host' => '',
                'port' => '', 'path' => '', 'query' => '', 'fragment' => ''
            ),
            (array) parse_url($url)
        );

        if (null == $component) {
            return $urlParts;
        }

        if (isset($urlParts[$component])) {
            return $urlParts[$component];
        }

        throw new \InvalidArgumentException("Invalid component '$component'");
    }

    /**
     * Vysklada url podla casti. Nieco ako opozit k parse_url.
     * Stoji za poznamku, ze neriesi ziadne pokrocile validovanie...
     *
     * @param array $urlParts
     * @return string
     */
    public static function build(array $urlParts)
    {
        $scheme = $user = $pass = $host = $hostname = $port = $path = $query = $fragment = '';

        $urlParts = array_merge(
            array(
                'scheme' => '', 'user' => '', 'pass' => '', 'host' => 'hostname',
                'port' => '', 'path' => '', 'query' => '', 'fragment' => ''
            ),
            $urlParts
        );
        extract($urlParts);

        // 'http://username:password@hostname/path?arg=value#anchor';
        $url = '//';

        if ("" != $scheme) {
            $url = "$scheme:$url";
        }

        if ("" != $user) {
            $url .= $user;
            if ("" != $pass) {
                $url .= ":$pass";
            }
            $url .= "@";
        }

        $url .= rtrim($host, "/");

        if ("" != $port) {
            $url .= ":$port";
        }

        $url .= "/" . ltrim($path, "/");

        if ("" != $query) {
            $url .= "?$query";
        }

        if ("" != $fragment) {
            $url .= "#$fragment";
        }

        return $url;
    }

    /**
     * HTTP_HOST versus SERVER_NAME?
     * http://stackoverflow.com/questions/2297403/http-host-vs-server-name
     * http://stackoverflow.com/questions/1459739/php-serverhttp-host-vs-serverserver-name-am-i-understanding-the-ma
     *
     * @param array $server
     * @param string $hostKey
     * @return string
     */
    public static function serverUrl(array $server = null, $hostKey = 'SERVER_NAME')
    {
        if (!$server) {
            $server = $_SERVER;
        }

        $port = !empty($server['SERVER_PORT']) ? $server['SERVER_PORT'] : 80;

        $url = "http" . (!empty($server['HTTPS']) ? 's' : '')
             . "://"
             . (!empty($server[$hostKey]) ? $server[$hostKey] : 'unknown-host')
             . (preg_match('/^(80|443)$/', $port) ? '' : ":$port")
             . (!empty($server['REQUEST_URI']) ? $server['REQUEST_URI'] : '/');

        return $url;
    }
}