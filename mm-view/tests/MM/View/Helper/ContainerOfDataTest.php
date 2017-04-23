<?php
namespace MM\View\Helper;

require_once __DIR__ . "/../_bootstrap.php";


/**
 * @group mm-view
 */
class ContainerOfDataTest extends \PHPUnit_Framework_TestCase
{
    public function testContainerIsEmptyByDefault()
    {
        $h = new ContainerOfData;
        $this->assertTrue(is_array($h->getContainer()));
        $this->assertEmpty($h->getContainer());
    }

    public function testHelperWorks()
    {
        $h = new ContainerOfData;
        $h->append(['a'=>1]);
        $h->append(['a'=>2]);
        $h->prepend(['a'=>0]);

        $expected = [
            ['a'=>0], ['a'=>1], ['a'=>2]
        ];

        $this->assertEquals($expected, $h->getContainer());

        $expected = [
            ['a'=>2], ['a'=>1], ['a'=>0]
        ];

        $this->assertEquals($expected, $h->reverse()->getContainer());
    }

    public function testContainerAcceptsAnyData()
    {
        $h = new ContainerOfData;
        $h->append('string');
        $h->append([0, 1]);
        $h->append(['multi' => ['dimensional', 'array']]);

        $this->assertEquals(3, count($h));
    }
}
