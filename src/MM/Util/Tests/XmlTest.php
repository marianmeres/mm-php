<?php
namespace MM\Util\Tests;

use MM\Util\Xml;
use PHPUnit\Framework\TestCase;

/**
 * @group mm-util
 */
class XmlTest extends TestCase {
	public function getWorkers() {
		return [
			'string' => function ($array, $indent = '  ') {
				return Xml::array2xml($array, 'root', $indent);
			},
		];
	}

	public function testArray2XmlAllWorkersWork() {
		$a = [
			'@attributes' => [
				'x' => 123,
				'y' => 456,
			],
			'a' => '>',
			'b' => '',
			'c' => [
				'x' => 1,
				'y' => [
					'z' => '<',
				],
			],
			'd' => [123, 456],
			'e' => [],
			'f' => [''],
			'g' => [
				['xx' => '>'],
				[
					'yy' => [
						'zz' => 'kk',
					],
				],
			],
			'mixed' => [
				'player' => ['id' => 1],
				'contractor' => [['id' => 2], ['id' => 3]],
			],
			'empty' => [[[[]]]],
			'empty_with_attr' => [
				'@attributes' => [
					'one' => 1,
					'two' => 2,
				],
			],
			'non_empty_with_attr' => [
				'@attributes' => [
					'three' => 3,
				],
				'regular' => 'value',
				'another' => ['values'],
			],
			'mixed_attr_and_non_assoc' => [
				'@attributes' => [
					'four' => 4,
				],
				'some',
				'another',
			],
			'zero' => 0,
			'null' => null,
			'false' => false,
		];

		$expected = '<?xml version="1.0" encoding="UTF-8"?>
<root x="123" y="456">
  <a>&gt;</a>
  <b/>
  <c>
    <x>1</x>
    <y>
      <z>&lt;</z>
    </y>
  </c>
  <d>123</d>
  <d>456</d>
  <e/>
  <f/>
  <g>
    <xx>&gt;</xx>
  </g>
  <g>
    <yy>
      <zz>kk</zz>
    </yy>
  </g>
  <mixed>
    <player>
      <id>1</id>
    </player>
    <contractor>
      <id>2</id>
    </contractor>
    <contractor>
      <id>3</id>
    </contractor>
  </mixed>
  <empty/>
  <empty_with_attr one="1" two="2"/>
  <non_empty_with_attr three="3">
    <regular>value</regular>
    <another>values</another>
  </non_empty_with_attr>
  <mixed_attr_and_non_assoc four="4">some</mixed_attr_and_non_assoc>
  <mixed_attr_and_non_assoc four="4">another</mixed_attr_and_non_assoc>
  <zero>0</zero>
  <null/>
  <false/>
</root>';

		$workers = $this->getWorkers();
		foreach ($workers as $name => $worker) {
			$xml = $worker($a, '  ');
			//prx($xml);
			$this->assertEquals(trim($xml), $expected, "Worker: $name");

			//prx(Xml::xml2array($xml));
			// nizsie pada, ale iba kvoli tomu, ze xml2array nikdy nevracia
			// prazdne stringy ale iba prazdne polia...
			//$this->assertEquals($a, Xml::xml2array($xml));
		}
	}

	public function testParseAsEmptyArray() {
		// 1 case: empty tag
		$xml = trim('<root><some/></root>');
		$parsed = Xml::xml2array($xml);
		$this->assertEquals([], $parsed['some']);

		// 2 case: empty string tag
		$xml = trim('<root><some></some></root>');
		$parsed = Xml::xml2array($xml);
		$this->assertEquals([], $parsed['some']);

		// 3 case: 0 je uz nula a nie pole
		$xml = trim('<root><some>0</some></root>');
		$parsed = Xml::xml2array($xml);
		$this->assertEquals(0, $parsed['some']);
	}
}
