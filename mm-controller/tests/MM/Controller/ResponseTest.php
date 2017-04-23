<?php
namespace MM\Controller;

require_once __DIR__ . "/_bootstrap.php";


/**
 * @group mm-controller
 */
class ResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testSettingAngGettingSegmentsWorks()
    {
        $r = new Response;

        $this->assertEquals(array(), $r->getBody());
        $this->assertNull($r->getBody('some'));
        $this->assertNull($r->getBody('default'));

        $r->setBody("some");
        $this->assertEquals("some", $r->getBody('default'));
        $this->assertEquals("some", $r->__toString());

        // append to default segment
        $r->setBody("thing", false);
        $this->assertEquals("something", $r->getBody('default'));

        // test sortnutia segmentov
        $r->setBody(".post", true, 'xxx');
        $r->setBody("pre.", true, 'aaa');
        $this->assertEquals("pre.something.post", $r->__toString());
    }

    public function testDefaultResponseStatusIsOk()
    {
        $rh = new Response();
        $this->assertEquals(200, $rh->getStatusCode());
        $this->assertEquals("HTTP/1.1 200 OK", $rh->getStatusAsString());
        $this->assertTrue($rh->isOk());
        $this->assertTrue($rh->isSuccess());

        // sanity checks
        $this->assertFalse($rh->isRedirect());
        $this->assertFalse($rh->isForbidden());
        $this->assertFalse($rh->isServerError());
        $this->assertFalse($rh->isNotFound());

    }

    public function testInvalidStatusCodeThrows()
    {
        $this->setExpectedException("MM\Controller\Exception");
        $rh = new Response();
        $rh->setStatusCode(123456);
    }

    public function testSettingHeadersWorks()
    {
        $rh = new Response();
        $rh->setHeader("k1", "v1");
        $rh->setHeader("K2", "v2");

        $this->assertEquals("v1", $rh->getHeader("K1"));
        $this->assertEquals("v2", $rh->getHeader("k2"));
    }

    public function testActualHeadersSendingWorks()
    {
        $rh = new Response();
        $rh->setHeader("k1", "v1");

        try {
            $rh->send();
        } catch (\Exception $e) {
            // pokial sa nezmeni lokalizacia a verzia php, tak by to malo fungovat
            if (!preg_match("/headers already sent/", $e->getMessage())) {
                $this->fail("Most likely failed... (was expecting different native error message)");
            }
            return;
        }

        // tu ako zistujem... sa vyssie chova rozne napriec roznymi instalaciami
        // php, takze toto brat s rezervou najskor... ani headers_sent() sa nezda
        // fungovat spolahlivo, dalsia moznost by mozno bola xdebug_headers_nieco...
        $this->fail("Seems like headers were not sent, but this may not be accurate...");
    }

    public function testKyesAreConventionalyNormalized()
    {
        $rh = new Response();
        $rh->setHeader("some-key", "1");
        $h = $rh->getHeaders();
        $this->assertTrue(isset($h['Some-Key']));
    }

    public function testSetCookieWorkflowWorks()
    {
        $dur = 60*60*24;
        $exp = time() + (60*60*24*7);

        $r = new Response();
        $r->setHeader("some", "not-a-cookie");
        $r->setCookie("a", "b");
        $r->setCookie("c", "d", array(
            "path"     => "/",
            'domain'   => "www.nba.com",
            'duration' => $dur,
        ));
        $r->setCookie("e", "f", array(
            'httponly' => "www.nba.com",
            'secure'   => 1,
            'expires'  => $exp,
        ));

        $headers = $r->getHeaders();
        $this->assertCount(2, $headers);
        $this->assertTrue(is_array($headers['Set-Cookie']));
        $this->assertCount(3, $headers['Set-Cookie']);

        $c = $r->getCookies();
        $this->assertCount(3, $c);

        $this->assertEquals("a=b", $c[0]);
        $this->assertEquals($c[1],
            "c=d; domain=www.nba.com; path=/; expires="
            . date(\DateTime::COOKIE, time() + $dur)
        );
        $this->assertEquals($c[2],
            sprintf("e=f; expires=%s; secure; httponly", date(\DateTime::COOKIE, $exp))
        );

        // _COOKIE
        $this->assertEquals($_COOKIE['a'], 'b');
        $this->assertEquals($_COOKIE['c'], 'd');
        $this->assertEquals($_COOKIE['e'], 'f');

        //
        $c = $r->getCookie("a");
        $this->assertEquals($c, array('a', 'b', array()));

        $c = $r->getCookie("c");
        $this->assertEquals($c, array('c', 'd', array(
            'domain' => 'www.nba.com',
            'path' => '/',
            'expires' => date(\DateTime::COOKIE, time() + $dur),
        )));

        $c = $r->getCookie("e");
        $this->assertEquals($c, array('e', 'f', array(
            'expires' => date(\DateTime::COOKIE, $exp),
            'secure' => null,
            'httponly' => null,
        )));

        $this->assertNull($r->getCookie('notset'));

        // unsetneme
        $r->unsetCookie('a');
        $r->unsetCookie('c');

        $this->assertFalse(array_key_exists('a', $_COOKIE));
        $this->assertFalse(array_key_exists('c', $_COOKIE));
        $this->assertEquals($_COOKIE['e'], 'f');

        // ale vyssi unset musi stale existovat v headeri s "expires" casom
        // v minulosti
        $c = $r->getCookie("a");
        $this->assertTrue(strtotime($c[2]['expires']) < time());

        $c = $r->getCookie("c");
        $this->assertTrue(strtotime($c[2]['expires']) < time());
    }

    public function testCookiesAreBeingSendAsRegularHeaders()
    {
        $r = new Response();
        $r->setCookie("a", "b");
        try {
            // toto musi padnut, lebo sme v cli, kde uz output zacal
            $r->send();
            $this->fail("should have failed on 'headers already sent'");
        } catch (\Exception $e) {
            $this->assertEquals(1,
                preg_match("/headers already sent/i", $e->getMessage())
            );
        }
    }
}