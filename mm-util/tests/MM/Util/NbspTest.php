<?php
/**
 * @author Marian Meres
 */
namespace MM\Util;

require_once __DIR__ . "/_bootstrap.php";

/**
 * @group mm-util
 */
class NbspTest extends \PHPUnit_Framework_TestCase
{
    public function testNbspWorks()
    {
        $map = array(
            '' => null,
            ' ' => null,
            'foo bar  baz   bat  ' => null,
            "2 foo" => '2&nbsp;foo',
            "22 foo" => null,
            "ff 777" => null,
            "f 777" => 'f&nbsp;777',
            'f foo' => 'f&nbsp;foo',
            "f \nfoo" => 'f&nbsp;foo',
            " f foo " => ' f&nbsp;foo ',
            " f \nfoo " => ' f&nbsp;foo ',
            " f \nfoo f\n\nfoo" => ' f&nbsp;foo f&nbsp;foo',
            "A tak sa pridal k nim." => "A&nbsp;tak sa pridal k&nbsp;nim.",
            "jedál budete" => null,
            "zahŕňa xx" => null,
            "ďaľším sdf" => null,
            "do týždňa v utorky" => "do týždňa v&nbsp;utorky",
            // hm...
            "<a href=''" => null,
            "<i class='" => null,
            "/i foo" => null,
            '\i foo' => null,
            ' :i foo' => null,
            ' ?i foo' => null,
            ' !i foo' => null,
            ' =i foo' => null,
            ' -i foo' => null,
            ' [i foo' => null,
            ' ]i foo' => null,
            ' §i foo' => null,
            ' *i foo' => null,
            ' ^i foo' => null,
            ' $i foo' => null,
            ' @i foo' => null,
            ' @@ foo' => null,
            "<div class='" => null,
        );

        foreach ($map as $src => $expected) {
            if ($expected === null) {
                $expected = $src;
            }
            //echo "\n$src : " . Nbsp::nbsp($src);

            $actual = Nbsp::nbsp($src);
            $this->assertEquals($expected, $actual);

            // a druhy krat ziadna zmena
            $this->assertEquals($expected, Nbsp::nbsp($actual));
        }
    }
}