<?php
/**
 * @author Marian Meres
 */
namespace MM\Util\Tests;

use MM\Util\Utf8Str;
use PHPUnit\Framework\TestCase;

/**
 * Realne tesujeme len sqlite
 *
 * @group mm-util
 */
class Utf8StrTest extends TestCase
{
	public function testUnaccentWorks()
	{
		$s1 =
			'Příliš žluťoučký kůň úpěl ďábelské ódy. ľňôä' .
			'Babí léto definitivně skončilo, zatáhne se a na horách začne sněžit';

		if (!class_exists('Normalizer', false)) {
			$this->markTestSkipped('Missing Normalizer (php intl extension)');
		}

		$this->assertEquals(
			strtolower(Utf8Str::normalizeUnaccentUtf8String($s1)),
			'prilis zlutoucky kun upel dabelske ody. lnoa' .
				'babi leto definitivne skoncilo, zatahne se a na horach zacne snezit'
		);
	}

	public function testUnaccentWorks2()
	{
		$s1 =
			'Příliš žluťoučký kůň úpěl ďábelské ódy. ľňôä' .
			'Babí léto definitivně skončilo, zatáhne se a na horách začne sněžit';

		$this->assertEquals(
			strtolower(Utf8Str::unaccentUtf8String($s1)),
			'prilis zlutoucky kun upel dabelske ody. lnoa' .
				'babi leto definitivne skoncilo, zatahne se a na horach zacne snezit'
		);
	}

	public function testUnaccentWorks3()
	{
		$s1 =
			'Příliš žluťoučký kůň úpěl ďábelské ódy. ľňôä' .
			'Babí léto definitivně skončilo, zatáhne se a na horách začne sněžit';

		$this->assertEquals(
			strtolower(Utf8Str::unaccent($s1)),
			'prilis zlutoucky kun upel dabelske ody. lnoa' .
				'babi leto definitivne skoncilo, zatahne se a na horach zacne snezit'
		);
	}
}
