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
 * @group mm-session
 */
class SaveHandlerDbTableTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DbUtilPdo
     */
    public $dbu;

    /**
     * @var SaveHandlerInterface
     */
    public $sh;

    /**
     * @var Response
     */
    public $response;

    public function setUpDbSql($vendor = 'sqlite')
    {
        $sql = DbTable::getDefaultSql();
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

    public function testOpenReturnsTrue()
    {
        $this->assertTrue($this->sh->open('foo', 'bar'));
    }

    public function testReadReturnsEmptyStringIfDataDoNotExist()
    {
        $this->assertEquals('', $this->sh->read('not-existing-id'));
    }

    public function testReadReturnsExistingValidDataIfFound()
    {
        // nieco vlozime
        $this->dbu->insert('_session', array(
            'id' => 123,
            'data' => 'foo',
            'valid_until' => time() + 1, //
            'lifetime' => 1440,
        ));
        $this->assertEquals('foo', $this->sh->read(123));
    }

    public function testReadReturnsEmptyStringIfDataExistsButAreExpiredAndPerformsDestroy()
    {
        $this->dbu->insert('_session', array(
            'id' => 123,
            'data' => 'foo',
            'valid_until' => time() - 10,
            'lifetime' => 5,
        ));
        $this->assertEquals(1, $this->dbu->fetchCount('_session'));

        $this->assertEquals('', $this->sh->read(123));

        // vyssie okrem toho ze nic nevrati, este aj destroyne expirovany zaznam
        $this->assertEquals(0, $this->dbu->fetchCount('_session'));
    }

    public function testWriteInsertsNewRow()
    {
        $this->assertEquals(0, $this->dbu->fetchCount('_session'));
        $this->assertTrue($this->sh->write(123, 'foo'));
        $this->assertEquals(1, $this->dbu->fetchCount('_session'));
    }

    public function testWriteUpdatesExistingRow()
    {
        $lifetime = 10; // sek
        $this->dbu->insert('_session', array(
            'id' => 123,
            'data' => 'foo',
            'valid_until' => time() - 1,
            'lifetime' => $lifetime,
        ));
        $this->assertEquals(1, $this->dbu->fetchCount('_session'));
        $this->assertTrue($this->sh->write(123, 'bar'));
        $this->assertEquals(1, $this->dbu->fetchCount('_session'));

        $row = $this->dbu->fetchRow("*", '_session', "id = '123'");

        $this->assertEquals('bar', $row['data']);

        // tu overime, ze update aj touchuje valid_until
        $this->assertTrue($row['valid_until'] > time());
    }

    public function testDestroyDeletesRow()
    {
        foreach (array(1,2) as $i) {
            $this->dbu->insert('_session', array(
                'id' => $i, 'data' => "foo$i", 'valid_until' => time() + 10
            ));
        }
        $this->assertEquals(2, $this->dbu->fetchCount('_session'));
        $this->assertTrue($this->sh->destroy(1));
        $this->assertEquals(1, $this->dbu->fetchCount('_session'));
        $this->assertEquals(0, $this->dbu->fetchCount('_session', "id = '1'"));
    }

    public function testGarbageCollectWorks()
    {
        foreach (array(0,1,2) as $i) {
            $this->dbu->insert('_session', array(
                'id' => $i, 'data' => "foo$i",
                'valid_until' => time() + ($i * 10),
                'lifetime' => 1
            ));
        }
        $this->assertEquals(3, $this->dbu->fetchCount('_session'));

        // gc musi zmazat iba id 3
        $this->assertTrue($this->sh->gc('ignored...')); // parameter je ignorovany

        $this->assertEquals(2, $this->dbu->fetchCount('_session'));
        $this->assertEquals(0, $this->dbu->fetchCount('_session', "id = '3'"));
    }

    public function testTopLevelRememberMeAndForgetMeWorks()
    {
        Session::setSaveHandler($this->sh);
        Session::setResponse($this->response);

        // teraz nemame expires
        $cookie = $this->response->getCookie(Session::getName());
        $this->assertFalse(isset($cookie[2]['expires']));

        // no a toto je kriticka cast
        $rememberMeSec = 12345;
        Session::rememberMe($rememberMeSec);
        $cookie = $this->response->getCookie(Session::getName());
        $this->assertTrue(isset($cookie[2]['expires']));
        $expires = date_create($cookie[2]['expires']);

        $t = time() + $rememberMeSec;
        $d = date_format($expires, 'U');
        $this->assertTrue(
            $t == $d || abs($t-$d) == 1
            // ak je v tom cert a sme prave trafili prechod sekund... treba ist
            // dnes podat lotto....
        );

        // vyssi remember me musi byt efektivny aj v db
        $this->sh->write(123, 'foo'); // tu musim rucne zapisat...
        $this->assertEquals(
            $rememberMeSec, $this->dbu->fetchOne('lifetime', '_session')
        );

        // teraz zabudneme a cookie musi expirovat
        Session::forgetMe();
        $cookie = $this->response->getCookie(Session::getName());
        $d = date_format(date_create($cookie[2]['expires']), 'U');
        $this->assertTrue($d < time()); // cas v minulosti
        $this->assertEmpty($cookie[1]); // toto nie je nevyhnutne, vyssi cas je podstatny

        // kedze sme vyssie zabudli, lifetime musi byt zaporny
        $this->sh->write(123, 'foo'); // tu musim rucne zapisat...
        $this->assertTrue(
            $this->dbu->fetchOne('lifetime', '_session') < 0
        );

        // a tym padom gc efektivny (ak by mal zbehnut)
        $this->sh->gc('whatever');
        $this->assertEquals(0, $this->dbu->fetchCount('_session'));
    }

    public function testRememberSessionSetsCorrectCookie()
    {
        // tu testujeme to, ze sessiona ktora bola oznacena ako "remember me"
        // tak sa tak aj nainicializuje

        $nsUsr = Session::getNsUsr();
        $nsSys = Session::getNsSys();

        // tu natvrdo podhodime data
        $rememberMeSec = 12345;
        $_SESSION[$nsSys]['cookie_lifetime'] = $rememberMeSec;

        Session::setSaveHandler($this->sh);
        Session::setResponse($this->response);
        Session::start();

        $cookie = $this->response->getCookie(Session::getName());
        $this->assertTrue(isset($cookie[2]['expires']));
        $expires = date_create($cookie[2]['expires']);

        $t = time() + $rememberMeSec;
        $d = date_format($expires, 'U');
        $this->assertTrue(
            $t == $d || abs($t-$d) == 1
            // ak je v tom cert a sme prave trafili prechod sekund... treba ist
            // dnes podat lotto....
        );
    }

    public function testSaveHandlerDefaultLifetimeIsPhpIniValue()
    {
        $this->assertTrue($this->sh->write(123, 'foo'));
        $this->assertEquals(
            $this->dbu->fetchOne('lifetime', '_session'),
            ini_get('session.gc_maxlifetime')
        );
    }

}