<?php
/**
 * @author Marian Meres
 */
namespace MM\Session;

use MM\Session\SaveHandler\DbTable;
use MM\Session\Session;
use MM\Util\DbUtilPdo;
use MM\Util\SqlHelper;
use MM\Controller\Response;

require_once __DIR__ . "/_bootstrap.php";

/**
 * @group pf-session
 */
class SaveHandlerDbTableCustomFieldsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DbUtilPdo
     */
    public $dbu;

    /**
     * @var DbTable
     */
    public $sh;

    /**
     * @var Response
     */
    public $response;

    public function setUpDbSql($vendor = 'sqlite')
    {
        $sql = DbTable::getDefaultSql();

        $sql .= "\nalter table _session add user_id int;";

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

        $this->sh = new SaveHandler\DbTable(array(
            'dbu' => $this->dbu
        ));

        $this->response = new Response();

        Session::resetToOutOfTheBoxState($mock = true);

        ini_set('session.gc_maxlifetime', 1440); // php default
    }

    public function tearDown()
    {
        Session::resetToOutOfTheBoxState($mock = false);
    }

    public function testReadingNonExistingCustomFieldsReturnsNull()
    {
        $this->dbu->insert('_session', array(
            'id' => 123,
            'data' => 'foo',
            'valid_until' => time() + 10, //
            'lifetime' => 1440,
        ));

        $this->assertEquals('foo', $this->sh->read(123));

        $this->assertEquals(null, $this->sh->getCustomField('foo'));
    }

    public function testReadingNonExistingCustomFieldsThrowsInStrictMode()
    {
        $this->dbu->insert('_session', array(
            'id' => 123,
            'data' => 'foo',
            'valid_until' => time() + 10, //
            'lifetime' => 1440,
        ));

        $this->assertEquals('foo', $this->sh->read(123));

        $this->setExpectedException("\MM\Session\Exception");
        $this->sh->getCustomField('foo', true);
    }

    public function testReadingExistingCustomFieldsWorks()
    {
        $this->dbu->insert('_session', array(
            'id' => 123,
            'data' => 'foo',
            'valid_until' => time() + 10, //
            'lifetime' => 1440,
            'user_id' => 456
        ));

        $this->assertEquals('foo', $this->sh->read(123));

        $this->assertEquals(456, $this->sh->getCustomField('user_id'));
    }

    public function testWritingCustomKeysThrowsIfDbHasNoSuchColumns()
    {
        $this->setExpectedException("\PDOException");
        $this->sh->setCustomField('foo', 'bar');
        $this->sh->write(123, 'baz');
    }

    public function testWritingDbKnownCustomKeysWorks()
    {
        $this->sh->setCustomField('user_id', 456);
        $this->sh->write(123, 'baz');

        $this->assertEquals(456, $this->sh->getCustomField('user_id'));

        $this->assertEquals(
            456, $this->dbu->fetchOne("user_id", "_session", ['id' => 123])
        );
    }
}