<?php
namespace MM\Util\Tests;

use ErrorException;
use MM\Util\ClassUtil;
use PHPUnit\Framework\TestCase;

/**
 * @group mm-util
 */
class ClassUtilTest extends TestCase
{
	public function testGetLastSegmentNameWorks()
	{
		$data = [
			'a' => 'a',
			'B' => 'B',
			'\c' => 'c',
			'_d' => 'd',
			'Some_Under_Scored' => 'Scored',
			'\Some\Name\Spaced' => 'Spaced',
			'Some\Name\Spaced2' => 'Spaced2',
		];
		foreach ($data as $k => $v) {
			$this->assertEquals(ClassUtil::getLastSegmentName($k), $v);
		}
	}

	public function testClassUsesDeepWorks()
	{
		$expected = [
			'MM\Util\Tests\ClassUtil\HeyTrait',
			'MM\Util\Tests\ClassUtil\HeyTrait2',
			'MM\Util\Tests\ClassUtil\HeyTrait3',
		];
		$actual = ClassUtil::classUsesDeep('MM\Util\Tests\ClassUtil\Potomok');

		$this->assertEquals(count($expected), count($actual));

		// kedze poradie najskor neviem garantovat, tak si to loopneme
		// lebo priame porovnanie by nemuselo sediet
		foreach ($expected as $trait) {
			$this->assertTrue(isset($actual[$trait]));
		}
	}

	public function testClassExistsIsAutoloadableCheckWorks()
	{
		$this->assertTrue(ClassUtil::classExists(\MM\Util\Tests\ClassUtil\Dummy::class));
		$this->assertFalse(ClassUtil::classExists('Whatever\Not\Existing'));

		// toto je pripad, kde fajl existuje spravne ale neobsahuje definiciu classu
		$this->assertFalse(ClassUtil::classExists('MM\Util\Tests\ClassUtil\Dummy3'));
	}

	public function testClassExistsPhpErrorInClassFilenameIsRethrown()
	{
		$this->expectException(ErrorException::class);
		ClassUtil::classExists('MM\Util\Tests\ClassUtil\Dummy2');
	}

	public function testClassExistsPhpErrorExceptionInClassIsRethrown()
	{
		$this->expectException(ErrorException::class);
		ClassUtil::classExists('MM\Util\Tests\ClassUtil\DummyChild');
	}
}
