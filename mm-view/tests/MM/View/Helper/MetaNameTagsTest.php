<?php
namespace MM\View\Helper;

require_once __DIR__ . "/../_bootstrap.php";


/**
 * @group mm-view
 */
class MetaNameTagsTest extends \PHPUnit_Framework_TestCase
{
    public function testMetaNameTagsWorks()
    {
        $h = new MetaNameTags();

        $h->set('description', 'foo');
        $h->set('description', 'foo2');

        $this->assertCount(1, $h);

        $this->assertEquals("<meta name='description' content='foo2'/>\n", "$h");

        $this->assertTrue($h->has('DESCription'));
        $this->assertFalse($h->has('foo'));
    }

}
