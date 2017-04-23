<?php
/**
 * @author Marian Meres
 */
namespace MM\Session;

use MM\Session\SaveHandler\DbTableUniqueAddon;
use MM\Session\Session;
use MM\Util\DbUtilPdo;
use MM\Util\SqlHelper;

require_once __DIR__ . "/_bootstrap.php";

/**
 * @group mm-session
 */
class SaveHandlerDbTableUniqueAddonTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DbUtilPdo
     */
    public $dbu;

    /**
     * @var SaveHandler\DbTableUniqueAddon
     */
    public $sh;

    public function setUpDbSql($vendor = 'sqlite')
    {
        $sql = DbTableUniqueAddon::getDefaultSql();
        return SqlHelper::getVendorSql($sql, $vendor);
    }

    public function setUp()
    {
        // $this->dbu = new DbUtilPdo(new \PDO("sqlite::memory:"));
        if (!defined("MM_SESSION_PDO_JSON_CONFIG")) {
            die("MM_SESSION_PDO_JSON_CONFIG not defined");
        }
        $this->dbu = new DbUtilPdo(
            json_decode(MM_SESSION_PDO_JSON_CONFIG, true)
        );

        $sql = $this->setUpDbSql(MM_SESSION_DB_VENDOR);
        $this->dbu->getResource()->exec($sql);

        $this->sh = new SaveHandler\DbTableUniqueAddon(array(
            'dbu' => $this->dbu,
            'uniqueName' => 'user_id',
        ));

        ini_set('session.gc_maxlifetime', 1440); // php default
    }

    public function testOpenReturnsTrue()
    {
        $this->assertTrue($this->sh->open('foo', 'bar'));
    }

    public function testReadReturnsEmptyStringIfDataDoNotExist()
    {
        $this->assertEquals('', $this->sh->read('not-existing-id'));
    }

    public function testReadReturnsExistingValidDataIfFoundAndUniqueAddonIsSet()
    {
        // nieco vlozime
        $this->dbu->insert('_session', array(
            'id' => 123,
            'data' => 'foo',
            'valid_until' => time() + 10,
            'lifetime' => 1440,
            'user_id' => 2,
        ));
        $this->assertEquals('foo', $this->sh->read(123));
        $this->assertEquals(2, $this->sh->uniqueValue);
    }

    public function testReadReturnsEmptyStringIfDataExistsButAreExpiredAndPerformsDestroy()
    {
        $this->dbu->insert('_session', array(
            'id' => 123,
            'data' => 'foo',
            'valid_until' => time() - 10,
            'lifetime' => 5,
            'user_id' => 2
        ));
        $this->assertEquals(1, $this->dbu->fetchCount('_session'));

        $this->assertEquals('', $this->sh->read(123));

        // vyssie okrem toho ze nic nevrati, este aj destroyne expirovany zaznam
        $this->assertEquals(0, $this->dbu->fetchCount('_session'));

    }

    public function testWriteInsertsNewRowIncludingCustomUniqueAddon()
    {
        $this->sh->uniqueValue = 456;

        $this->assertEquals(0, $this->dbu->fetchCount('_session'));
        $this->assertTrue($this->sh->write(123, 'foo'));

        $all = $this->dbu->fetchAll("*", '_session');
        $this->assertEquals(1, count($all));
        $this->assertEquals(456, $all[0]['user_id']);
    }

    public function testWriteUpdatesExistingRowAndAssertsUniqueConstraint()
    {
        $this->dbu->insert('_session', array(
            'id' => 123, 'data' => 'foo', 'valid_until' => time() - 10,
            'lifetime' => 1440,
        ));

        // zaroven vlozime aj iny s konfliktnym unique ideckom
        $this->dbu->insert('_session', array(
            'id' => 555, 'data' => 'some', 'valid_until' => time(),
            'user_id' => 456, // toto je konfliktny udaj
            'lifetime' => 1440,
        ));

        $this->assertEquals(2, $this->dbu->fetchCount('_session'));

        // pridame unique zaznam
        $this->sh->uniqueValue = 456;

        // tento zapis musi mimo ine zmazat id 555
        $this->assertTrue($this->sh->write(123, 'bar'));

        $all = $this->dbu->fetchAll("*", '_session');

        // uz musi existovat len jeden zaznam (data su checknute nizsie)
        $this->assertEquals(1, count($all));
        $row = $all[0];//$this->dbu->fetchRow("*", '_session', "id = '123'");

        $this->assertEquals(123, trim($row['id'])); // trim lebo postgres char
        $this->assertEquals('bar', $row['data']);

        // muselo vlozit aj custom zaznam
        $this->assertEquals(456, $row['user_id']);

        // tu overime, ze update aj touchuje valid_until
        $this->assertTrue($row['valid_until'] > time());


        // este tu skusime manualne znova podhodit konfliktne data
        $this->dbu->execute("update _session set user_id = 999"); // nejaky not null
        $this->dbu->insert('_session', array(
            'id' => 555, 'data' => 'some', 'valid_until' => time(),
            'user_id' => 456, // toto je konfliktny udaj
        ));

        // a teraz ked zapiseme (co bude update), musi to dopadnut rovnako ako
        // vyssie
        $this->assertEquals(2, $this->dbu->fetchCount('_session'));
        $this->assertTrue($this->sh->write(123, 'bar'));
        $all = $this->dbu->fetchAll("*", '_session');
        $this->assertEquals(1, count($all));
        $row = $all[0];
        $this->assertEquals(456, $row['user_id']);
    }

    public function testReadingUniqueDoesNotCollideWithCustom()
    {
        $this->dbu->insert('_session', array(
            'id' => 123,
            'data' => 'foo',
            'valid_until' => time() - 10,
            'lifetime' => 5,
            'user_id' => 2 // toto je unique
        ));

        //prx($this->sh->getCustomFields());
    }
}