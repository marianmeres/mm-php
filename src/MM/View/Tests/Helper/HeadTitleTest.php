<?php declare(strict_types=1);
namespace MM\View\Tests\Helper;

use MM\View\Helper\HeadTitle;
use MM\View\View;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../_bootstrap.php';

/**
 * @group mm-view
 */
final class HeadTitleTest extends TestCase {
	public function testHeadTitleIsEmptyByDefault() {
		$h = new HeadTitle();
		$this->assertEquals('<title></title>', trim((string) $h));
	}

	public function testHeadTitleWorks() {
		$h = new HeadTitle();
		$h->setSeparator(':');
		$h->append('>');
		$h->append('>');
		$h->prepend('pre');
		$h->append('post');

		$this->assertEquals('<title>pre:&gt;:&gt;:post</title>', trim((string) $h));
		$h->setDoEscape(false);
		$this->assertEquals('<title>pre:>:>:post</title>', trim((string) $h));

		$h->setContainer([]);
		$this->assertEquals('<title></title>', trim((string) $h));

		$h->append('a')
			->append('b')
			->reverse();
		$this->assertEquals('<title>b:a</title>', trim((string) $h));
	}

	public function testWithView() {
		$v = new View();
		$this->assertEquals('<title>foo</title>', trim((string) $v->headTitle('foo')));
	}

	public function testReplaceLastWorks() {
		$h = new HeadTitle();
		$h->setSeparator(':');
		$h->append('a');
		$h->append(['b', 'c']);
		$this->assertEquals('<title>a:b:c</title>', trim((string) $h));

		$h->replaceLast('d');
		$this->assertEquals('<title>a:b:d</title>', trim((string) $h));

		$h->replaceLast(null);
		$this->assertEquals('<title>a:b</title>', trim((string) $h));

		$h->replaceLast('');
		$this->assertEquals('<title>a</title>', trim((string) $h));
	}
}
