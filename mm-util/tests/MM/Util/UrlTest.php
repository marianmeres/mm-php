<?php
/**
 * @author Marian Meres
 */
namespace MM\Util;

require_once __DIR__ . "/_bootstrap.php";

/**
 * @group mm-util
 */
class UrlTest extends \PHPUnit_Framework_TestCase
{
    public function testParseAndBuildWorks()
    {
        $urls = array(
            'http://username:password@hostname/path?arg=value#anchor',
            'https://example.com/?a=some/new&foo=bar',
            'http://abc.some.com:8080/', // musi koncit s /
            'http://abc.some.com:8080/#some',
            'http://abccom:8080/?a=some/new',
            'http://abccom:8080/lon/gpa/th/',
        );
        foreach ($urls as $url) {
            $this->assertEquals($url, Url::build(\MM\Util\Url::parse($url)));
        }
    }
}