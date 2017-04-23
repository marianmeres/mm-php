<?php
/**
 * @author Marian Meres
 */
namespace MM\Session;

use MM\Session\Session;
use MM\Session\SaveHandler\Mock;
// use MM\Util\SqlHelper;

require_once __DIR__ . "/_bootstrap.php";

/**
 * @group mm-session
 */
class SaveHandlerMockTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mock::$data = array();
        $this->sh = new Mock;
    }

    public function testSanityCheck()
    {
        $this->assertTrue($this->sh->open('ignored', 'ignored too'));
        $this->assertTrue($this->sh->write(123, 'foo'));
        $this->assertEquals("foo", Mock::$data[123]['data']);
        $this->assertEquals("foo", $this->sh->read(123));
        $this->assertTrue($this->sh->destroy(123));
        $this->assertTrue(!isset(Mock::$data[123]));
    }

}