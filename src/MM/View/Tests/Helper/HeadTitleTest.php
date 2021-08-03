<?php declare(strict_types=1);
namespace MM\View\Tests\Helper;

use MM\View\Helper\HeadTitle;
use MM\View\View;
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
		$this->assertEquals('<title></title>', (string) $h);
	}

	public function testHeadTitleWorks()
	{
		$h = new HeadTitle();
		$h->setSeparator(':');
		$h->append('>');
		$h->append('>', false);
		$h->prepend('pre');
		$h->append('post');

		$exp = '<title>pre:&gt;:>:post</title>';
		$this->assertEquals($exp, (string) $h);

		$h->setContainer([]);
		$this->assertEquals('<title></title>', (string) $h);

		$h->append('a')
			->append('b')
			->reverse();
		$this->assertEquals('<title>b:a</title>', (string) $h);
	}

	public function testWithView()
	{
		$v = new View();
		$this->assertEquals('<title>foo</title>', (string) $v->headTitle('foo'));
	}
}
