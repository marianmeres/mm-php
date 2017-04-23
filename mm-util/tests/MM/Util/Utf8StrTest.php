<?php
/**
 * @author Marian Meres
 */
namespace MM\Util;

require_once __DIR__ . "/_bootstrap.php";

/**
 * Realne tesujeme len sqlite
 *
 * @group mm-util
 */
class Utf8StrTest extends \PHPUnit_Framework_TestCase
{
    public function testUnaccentWorks()
    {
        $s1 = "Příliš žluťoučký kůň úpěl ďábelské ódy. ľňôä"
            . "Babí léto definitivně skončilo, zatáhne se a na horách začne sněžit";

        if (!class_exists("Normalizer", false)) {
            $this->markTestSkipped("Missing Normalizer (php intl extension)");
        }

        $this->assertEquals(
            strtolower(\MM\Util\Utf8Str::normalizeUnaccentUtf8String($s1)),
            "prilis zlutoucky kun upel dabelske ody. lnoa"
          . "babi leto definitivne skoncilo, zatahne se a na horach zacne snezit"
        );
    }

    public function testUnaccentWorks2()
    {
        $s1 = "Příliš žluťoučký kůň úpěl ďábelské ódy. ľňôä"
            . "Babí léto definitivně skončilo, zatáhne se a na horách začne sněžit";

        $this->assertEquals(
            strtolower(\MM\Util\Utf8Str::unaccentUtf8String($s1)),
            "prilis zlutoucky kun upel dabelske ody. lnoa"
          . "babi leto definitivne skoncilo, zatahne se a na horach zacne snezit"
        );
    }

    public function testUnaccentWorks3()
    {
        $s1 = "Příliš žluťoučký kůň úpěl ďábelské ódy. ľňôä"
            . "Babí léto definitivně skončilo, zatáhne se a na horách začne sněžit";

        $this->assertEquals(
            strtolower(\MM\Util\Utf8Str::unaccent($s1)),
            "prilis zlutoucky kun upel dabelske ody. lnoa"
          . "babi leto definitivne skoncilo, zatahne se a na horach zacne snezit"
        );
    }
}