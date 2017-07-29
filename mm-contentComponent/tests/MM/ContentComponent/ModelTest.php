<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 10/07/15
 * Time: 11:47
 */
namespace MM\ContentComponent;

require_once __DIR__ . "/_bootstrap.php";

/**
 * @group mm-contentComponent
 */
class ModelTest extends \PHPUnit_Framework_TestCase
{
    public function testComponenentModelDoesNotHaveFixedDataFieldsWorks()
    {
        $data = [
            'title' => 'untitled',
            'body' => null
        ];
        $m = new Model($data);

        $this->assertEquals($m->toArray(), array_merge($data, ['id' => null]));
        $this->assertFalse($m->isDirty());
    }

}