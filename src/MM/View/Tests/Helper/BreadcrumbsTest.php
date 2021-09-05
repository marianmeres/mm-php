<?php declare(strict_types=1);
namespace MM\View\Tests\Helper;

use MM\View\Helper\Breadcrumbs;
use MM\View\View;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../_bootstrap.php';

/**
 * @group mm-view
 */
final class BreadcrumbsTest extends TestCase {
	public function testBreadcrumbsWorks() {
		$h = new Breadcrumbs();

		$h->append(['label' => 'Home', 'href' => '/']);
		$h->append(['label' => 'Page', 'href' => '/page']);
		$out = $h->toString();
		// prx($out);

		// aspon nieco...
		$this->assertMatchesRegularExpression("/href='\/'/", $out);
		$this->assertMatchesRegularExpression("/>Home</", $out);
		$this->assertMatchesRegularExpression("/>Page</", $out);
		$this->assertDoesNotMatchRegularExpression("/href='\/page'/", $out);

	}
}
