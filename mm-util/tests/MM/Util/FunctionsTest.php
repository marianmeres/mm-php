<?php
namespace MM\Util;

require_once __DIR__ . "/_bootstrap.php";

/**
 * @group mm-util
 */
class FunctionsTest extends \PHPUnit_Framework_TestCase
{
    public function testRrmdirWorks()
    {
        FunctionLoader::load('rrmdir');

        $base = __DIR__ . "/_tmp";
        $dir  = "dir_" . trim(substr(microtime(), 2, 4));
        $path = "$base/$dir";

        // sanity checks + prepare
        $this->assertTrue(is_dir($base));
        $this->assertFalse(file_exists($path));

        mkdir("$path/a/b/c", 0777, true);
        file_put_contents("$path/a/a.txt", 'a');
        file_put_contents("$path/a/b/b.txt", 'b');
        file_put_contents("$path/a/b/c/c.txt", 'c');

        $this->assertTrue(is_dir("$path/a/b/c"));

        rrmdir($path);

        $this->assertFalse(file_exists($path));
        $this->assertTrue(file_exists($base));
    }

    public function testHmsWorks()
    {
        FunctionLoader::load('hms');

        $this->assertEquals("00:00:10", hms(10));
        $this->assertEquals("00:01:00", hms(60));
        $this->assertEquals("00:02:01", hms(121));
        $this->assertEquals("00:59:59", hms(3599));
        $this->assertEquals("01:00:00", hms(3600));
        $this->assertEquals("01:01:01", hms(3661));
        $this->assertEquals("01:59:59", hms(7199));
    }

    /**
     * @group humanReadableSeconds
     */
    public function testHumanReadableSecondsWorks()
    {
        FunctionLoader::load('humanReadableSeconds');

        $this->assertEquals("1 sec",   humanReadableSeconds(1));
        $this->assertEquals("59 secs", humanReadableSeconds(59));

        $this->assertEquals("1 min",   humanReadableSeconds(60));
        $this->assertEquals("2 mins",  humanReadableSeconds((60 * 2)));
        $this->assertEquals("2 mins",  humanReadableSeconds((60 * 2) + 29));
        $this->assertEquals("3 mins",  humanReadableSeconds((60 * 2) + 30));

        $this->assertEquals("1 hour",  humanReadableSeconds((60 * 60)));
        $this->assertEquals("1 hour",  humanReadableSeconds((60 * 60) + (60 * 29)));
        $this->assertEquals("2 hours", humanReadableSeconds((60 * 60) + (60 * 30)));

        $this->assertEquals("1 day",   humanReadableSeconds((60 * 60 * 24)));
        $this->assertEquals("1 day",   humanReadableSeconds((60 * 60 * 24) + (60 * 60 * 11)));
        $this->assertEquals("2 days",  humanReadableSeconds((60 * 60 * 24) + (60 * 60 * 12)));

        $this->assertEquals("1 week",  humanReadableSeconds((60 * 60 * 24 * 7)));
        $this->assertEquals("1 week",  humanReadableSeconds((60 * 60 * 24 * 7) + (60 * 60 * 24 * 3)));
        $this->assertEquals("2 weeks", humanReadableSeconds((60 * 60 * 24 * 7) + (60 * 60 * 24 * 4)));

        // tu je approx konstanta 4.35
        $this->assertEquals("1 month", humanReadableSeconds((60 * 60 * 24 * 7 * 5)));
        $this->assertEquals("1 month", humanReadableSeconds((60 * 60 * 24 * 7 * 5) + (60 * 60 * 24 * 7 * 1)));
        $this->assertEquals("2 months",humanReadableSeconds((60 * 60 * 24 * 7 * 5) + (60 * 60 * 24 * 7 * 3)));
        //prx(humanReadableSeconds(time()));
        $this->assertEquals("1 year",  humanReadableSeconds((60 * 60 * 24 * 7 * 4.35 * 12)));
        $this->assertEquals("1 year",  humanReadableSeconds((60 * 60 * 24 * 7 * 4.35 * 12) + (60 * 60 * 24 * 7 * 4.35 * 5)));
        $this->assertEquals("2 years", humanReadableSeconds((60 * 60 * 24 * 7 * 4.35 * 12) + (60 * 60 * 24 * 7 * 4.35 * 7)));
    }

    public function testMedianWorks()
    {
        FunctionLoader::load('median');

        // odd
        $this->assertEquals(5, median(1, 5, 2, 8, 7));
        $this->assertEquals(5, median(array(1, 5, 2, 8, 7)));

        // even
        $this->assertEquals(4, median(1, 6, 2, 8, 7, 2));
        $this->assertEquals(4, median(array(1, 6, 2, 8, 7, 2)));
    }

    public function testRandomTextWorks()
    {
        FunctionLoader::load('randomText');

        // naive
        $one = randomText();
        $two = randomText();

        $this->assertNotEquals($one, $two);
    }
}

