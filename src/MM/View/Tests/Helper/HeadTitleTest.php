<?php declare(strict_types=1);
namespace MM\View\Tests\Helper;

use MM\View\Helper\HeadTitle;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../_bootstrap.php';

/**
 * @group mm-view
 */
final class HeadTitleTest extends TestCase
{
	public function testHeadTitleIsEmptyByDefault()
	{
		$h = new HeadTitle();
		$this->assertEquals('', (string) $h);
	}

	public function testHeadTitleWorks()
	{
		$h = new HeadTitle();
		$h->setSeparator(':');
		$h->append('>');
		$h->append('>', false);
		$h->prepend('pre');
		$h->append('post');

		$exp = 'pre:&gt;:>:post';
		$this->assertEquals($exp, (string) $h);

		$h->setContainer([]);
		$this->assertEquals('', (string) $h);

		$h->append('a')
			->append('b')
			->reverse();
		$this->assertEquals('b:a', (string) $h);
	}
}
