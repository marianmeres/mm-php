<?php declare(strict_types=1);

namespace MM\Util\Tests;

use MM\Util\DbUtilPdo;
use MM\Util\SqlHelper;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/_bootstrap.php';

/**
 * Realne tesujeme len sqlite
 *
 * @group mm-util
 */
class DbUtilPdoTest extends TestCase {
	/**
	 * @var DbUtilPdo
	 */
	public $dbu;

	public function getTestTableSql($vendor = 'sqlite') {
		$sql = "
            drop table if exists _test;
            create table _test (
               id   {serial-primary-key},
               uid  char(32) not null,
               foo  varchar(255) null
            );

            create unique index _test_uid on _test (uid);
            insert into _test (uid, foo) values ('uid1', 'bull');
            insert into _test (uid, foo) values ('uid2', 'shit');
            insert into _test (uid, foo) values ('uid3', 'wow');
        ";
		return SqlHelper::getVendorSql($sql, $vendor);
	}

	protected function setUp(): void {
		// $this->dbu = new DbUtilPdo(new \PDO("sqlite::memory:"));
		if (!defined('MM_UTIL_PDO_JSON_CONFIG')) {
			die('MM_UTIL_PDO_JSON_CONFIG not defined');
		}
		$this->dbu = new DbUtilPdo(json_decode(MM_UTIL_PDO_JSON_CONFIG, true));

		$sql = $this->getTestTableSql(MM_UTIL_DB_VENDOR);
		$this->dbu->getResource()->exec($sql);
	}

	public function testDsnModeRequiresAtLeastDriverAndDatabaseForSqlite() {
		$this->expectException('\RuntimeException');
		DbUtilPdo::factoryResource([]);
	}

	public function testDsnModeRequiresAtLeastDriverAndDatabaseForSqlite2() {
		$this->expectException('\RuntimeException');
		DbUtilPdo::factoryResource([
			'driver' => 'sqlite',
		]);
	}

	public function testDsnModeRequiresAtLeastDriverAndDatabaseAndHostnameForOthers() {
		$this->expectException('\RuntimeException');
		DbUtilPdo::factoryResource([
			'driver' => 'pgsql',
			'database' => 'some',
		]);
	}

	public function testDsnModeRequiresAtLeastDriverAndDatabaseAndHostnameForOthers2() {
		$this->expectNotToPerformAssertions();
		// tu testujeme ze to nehodi
		DbUtilPdo::factoryResource(
			[
				'driver' => 'pgsql',
				'database' => 'some',
				'hostname' => 'juchu',
			],
			$debug = true,
		);
	}

	public function testDsnStringIsBuildCorrectlyForSqlite() {
		$debug = DbUtilPdo::factoryResource(
			[
				'driver' => 'sqlite',
				'database' => ':memory:',
			],
			true,
		);

		$this->assertEquals('sqlite::memory:', $debug['dsn']);
	}

	public function testDsnStringIsBuildCorrectlyForMysqlAndPgsql() {
		// toto je len na ilustraciu podporovanych zapisov
		$map = [
			'mysql' => 'mysql',
			'pdo_mysql' => 'mysql',
			'PDO_PGSQL' => 'pgsql',
			'pgsql' => 'pgsql',
		];
		foreach ($map as $def => $driver) {
			$d = DbUtilPdo::factoryResource(
				[
					'driver' => $def,
					'database' => 'some',
					'hostname' => 'server',
					'username' => 'hoho',
					'a' => 'b',
				],
				true,
			);

			$expected = [
				'dsn' => "$driver:host=server;dbname=some",
				'username' => 'hoho',
				'password' => null,
				'driver_options' => ['a' => 'b'],
			];

			$this->assertSame($expected, $d);
		}
	}

	public function testSqliteForeignKeysSupportIsEnabledByDefaultAndCanBeDisabled() {
		// defaultne je ON
		if ('sqlite' == $this->dbu->getDriverName()) {
			$this->assertEquals(
				1,
				$this->dbu->fetchOneSql('PRAGMA foreign_keys', null, ['limit' => null]),
			);
		}

		// a ak nahodou nechceme, vieme to zabezpecit
		$db = new DbUtilPdo();
		unset($db->autoInitCommands['sqlite']);
		$db->activateQueryLog();
		$db->setResource(new \PDO('sqlite::memory:'));
		$this->assertEquals(
			0,
			$db->fetchOneSql('PRAGMA foreign_keys', null, ['limit' => null]),
		);
		// prx($db->getQueryLog());
	}

	public function testFetchallReturnsArray() {
		$all = $this->dbu->fetchAll('*', '_test');
		$this->assertIsArray($all);
		$this->assertCount(3, $all);
		$this->assertEquals('wow', $all[2]['foo']);
	}

	public function testFetchallReturnsEmptyArrayWhenNothingFound() {
		$all = $this->dbu->fetchAll('*', '_test', 'id = 123');
		$this->assertIsArray($all);
		$this->assertEmpty($all);
	}

	public function testFetchallsqlReturnsArray() {
		$all = $this->dbu->fetchAllSql('select * from _test');
		$this->assertIsArray($all);
		$this->assertCount(3, $all);
		$this->assertEquals('wow', $all[2]['foo']);
	}

	public function testFetchallsqlReturnsEmptyArrayWhenNothingFound() {
		$all = $this->dbu->fetchAllSql('select * from _test', 'id = 123');
		$this->assertIsArray($all);
		$this->assertEmpty($all);
	}

	public function testFetchrowReturnsArray() {
		$row = $this->dbu->fetchRow('foo', '_test', null, ['debug' => 0]);
		$this->assertIsArray($row);
		$this->assertTrue(isset($row['foo']));
	}

	public function testFetchrowReturnsNullWhenNothingFound() {
		$row = $this->dbu->fetchRow('*', '_test', 'id = 123');
		$this->assertNull($row);
	}

	public function testFetchrowsqlReturnsArray() {
		$row = $this->dbu->fetchRowSql('select foo from _test');
		$this->assertIsArray($row);
		$this->assertTrue(isset($row['foo']));
	}

	public function testFetchrowsqlReturnsNullWhenNothingFound() {
		$row = $this->dbu->fetchRowSql('select * from _test where id = 123');
		$this->assertNull($row);
	}

	public function testFetchcolReturnsArrayOfFirstColInAllRows() {
		$col = $this->dbu->fetchCol('foo', '_test', null, [
			'order_by' => 'id',
			'debug' => 0,
		]);
		$this->assertSame(['bull', 'shit', 'wow'], $col);
	}

	public function testFetchcolReturnsNullWhenNothingFound() {
		$row = $this->dbu->fetchCol('*', '_test', 'id = 123');
		$this->assertNull($row);
	}

	public function testFetchcolsqlReturnsArrayOfFirstColInAllRows() {
		$col = $this->dbu->fetchColSql('select foo from _test', null, [
			'order_by' => 'id',
			'debug' => 0,
		]);
		$this->assertSame(['bull', 'shit', 'wow'], $col);
	}

	public function testFetchcolsqlReturnsNullWhenNothingFound() {
		$row = $this->dbu->fetchColSql('select * from _test', 'id = 123');
		$this->assertNull($row);
	}

	public function testFetchoneReturnsValue() {
		$res = $this->dbu->fetchOne('foo', '_test', null, [
			'order_by' => 'id',
			'debug' => 0,
		]);
		$this->assertEquals('bull', $res);
	}

	public function testFetchoneReturnsNullWhenNothingFound() {
		$res = $this->dbu->fetchOne('*', '_test', 'id = 123');
		$this->assertNull($res);
	}

	public function testFetchonesqlReturnsValue() {
		$res = $this->dbu->fetchOneSql('select foo from _test', null, [
			'order_by' => 'id',
			'debug' => 0,
		]);
		$this->assertEquals('bull', $res);
	}

	public function testFetchonesqlReturnsNullWhenNothingFound() {
		$res = $this->dbu->fetchOneSql('select * from _test', ['id' => 123]);
		$this->assertNull($res);
	}

	public function testFetchcountWorks() {
		$this->assertEquals(3, $this->dbu->fetchCount('_test'));
		$this->assertEquals(2, $this->dbu->fetchCount('_test', 'id IN (2, 3)'));
		$this->assertEquals(1, $this->dbu->fetchCount('_test', 'id = 1'));
		$this->assertEquals(0, $this->dbu->fetchCount('_test', 'id = 123'));
	}

	public function testWhereConditionAsStringWorks() {
		$all = $this->dbu->fetchAll('*', '_test', 'id = 3');
		$this->assertIsArray($all);
		$this->assertCount(1, $all);
		$this->assertEquals('wow', $all[0]['foo']);
	}

	public function testWhereConditionAsArrayWorks() {
		$all = $this->dbu->fetchAll('*', '_test', ['id' => 3], ['debug' => 0]);
		$this->assertIsArray($all);
		$this->assertCount(1, $all);
		$this->assertEquals('wow', $all[0]['foo']);
	}

	public function testBuildWhereWorks() {
		// 1. magic string key "=" noescape
		$sql = $this->dbu->buildSqlWhere([
			'=' => 'as is',
		]);
		$this->assertEquals('as is', trim($sql));

		// // 2. value as array
		$sql = $this->dbu->buildSqlWhere([
			'x' => [1, 2],
		]);
		$q = substr($this->dbu->qv('x'), 0, 1); // quote znak
		$this->assertTrue(false !== strpos($sql, "IN ({$q}1{$q},{$q}2{$q})"));

		// // 3. value as closure
		// $sql = $this->dbu->buildSqlWhere(array(
		//     "x" => function () {
		//         return '= now()';
		//     }
		// ));
		// $this->assertTrue(false !== strpos($sql, "= now()"));

		// simulacia praktickej situacie
		// select * from table where id <> 1 or x = 2 and y in (1,2) and time < now()
		$sql = $this->dbu->buildSqlWhere([
			'id!' => 1,
			'=' => 'or x = 2', // toto neescapne
			'y' => [1, 2],
			'time<' => date('Y-m-d'),
			'time<' => function () {
				return 'now()';
			},
			'time<' => function () {
				return 'trim';
			},
			'time>' => 'trim',
		]);

		// prx($sql);
	}

	public function testMagicStringsInWhereColNamesWork() {
		// quotovaci znak pre akutalny driver
		$q = substr($this->dbu->qv('x'), 0, 1);

		$expected = [
			'!=' => '<>',
			'<>' => '<>',
			'<=' => '<=',
			'>=' => '>=',
			'!~' => 'NOT LIKE',

			'!' => '<>',
			'<' => '<',
			'>' => '>',
			'=' => '=',
			'~' => 'LIKE',
		];

		foreach ($expected as $notation => $res) {
			$sql = $this->dbu->buildSqlWhere([
				"foo$notation" => 'bar',
			]);
			// echo "$sql\n";
			$regExp = "/foo.\s+$res\s+{$q}bar{$q}/";
			$this->assertEquals(
				1,
				preg_match($regExp, $sql),
				"mismatch in: $notation : $res : $sql : $regExp",
			);
		}

		// array notation
		$expected = [
			'!=' => 'NOT',
			'<>' => 'NOT',
			'<=' => '', // bude ignorovane
			'>=' => '', // bude ignorovane
			'!~' => '', // bude ignorovane

			'!' => 'NOT',
			'<' => '', // bude ignorovane
			'>' => '', // bude ignorovane
			'=' => '', // bude ignorovane
			'~' => '', // bude ignorovane
		];

		foreach ($expected as $notation => $res) {
			$sql = $this->dbu->buildSqlWhere([
				"foo$notation" => [1],
			]);
			// echo "$sql\n";
			$regExp = "/foo.\s*$res\s+IN\s+\({$q}1{$q}/";
			$this->assertEquals(
				1,
				preg_match($regExp, $sql),
				"mismatch in: $notation : $res : $sql : $regExp",
			);
		}

		// null v hodnote
		foreach ($expected as $notation => $res) {
			$sql = $this->dbu->buildSqlWhere([
				"foo$notation" => null,
			]);
			// echo "$sql\n";
			$regExp = "/foo.\s+IS\s*$res\s+NULL/";
			$this->assertEquals(
				1,
				preg_match($regExp, $sql),
				"mismatch in: $notation : $res : $sql : $regExp",
			);
		}
	}

	public function testInsertWorks() {
		$this->assertEquals(3, $this->dbu->fetchCount('_test'));

		$affectedRows = $this->dbu->insert('_test', [
			'uid' => 'uid-foo',
		]);

		$this->assertEquals(4, $this->dbu->fetchCount('_test'));
		$this->assertEquals(1, $this->dbu->fetchCount('_test', ['uid' => 'uid-foo']));

		$this->assertEquals(1, $affectedRows);
	}

	public function testLastInsertIdWorks() {
		// setUp setuje 3
		$this->assertEquals(3, $this->dbu->lastInsertId());
		$this->dbu->execute("insert into _test (uid) values ('mrtepokokot')");
		$this->assertEquals(4, $this->dbu->lastInsertId());
	}

	public function testInsertReturnsFalseOnEmptyData() {
		$this->assertFalse($this->dbu->insert('_test', []));
	}

	public function testUpdateAllWorks() {
		$this->assertEquals(0, $this->dbu->fetchCount('_test', ['foo' => 'xyz']));
		$result = $this->dbu->update('_test', ['foo' => 'xyz'], '1=1');
		$this->assertEquals(3, $this->dbu->fetchCount('_test', ['foo' => 'xyz']));

		// affectedRows ALE POZOR! toto nemusi nevyhnutne znamenat pocet updated rows
		// chovanie sa lisi od konkretnych vendorov
		// $this->assertEquals(3, $result->getGeneratedValue());
	}

	public function testUpdateOneWorks() {
		$this->assertEquals(0, $this->dbu->fetchCount('_test', ['foo' => 'xyz']));
		$result = $this->dbu->update('_test', ['foo' => 'xyz'], 'id = 1');
		$this->assertEquals(1, $this->dbu->fetchCount('_test', ['foo' => 'xyz']));
	}

	public function testUpdateHasNoEffectOnNotMatchingWhereCondition() {
		$this->assertEquals(0, $this->dbu->fetchCount('_test', ['foo' => 'xyz']));
		$result = $this->dbu->update('_test', ['foo' => 'xyz'], ['id' => 213]);
		$this->assertEquals(0, $this->dbu->fetchCount('_test', ['foo' => 'xyz']));
	}

	public function testUpdateReturnsFalseOnEmptyData() {
		$this->assertFalse($this->dbu->update('_test', [], '1=1'));
	}

	public function testDeleteWholeTableWorks() {
		$this->assertEquals(3, $this->dbu->fetchCount('_test'));
		$this->dbu->delete('_test', true);
		$this->assertEquals(0, $this->dbu->fetchCount('_test'));
	}

	public function testDeleteWithWhereConditionWorks() {
		$this->assertEquals(3, $this->dbu->fetchCount('_test'));
		$this->dbu->delete('_test', ['id' => 1]);
		$this->assertEquals(2, $this->dbu->fetchCount('_test'));
	}

	public function testDeleteWithNotMatchedWhereConditionDeletesNothing() {
		$this->assertEquals(3, $this->dbu->fetchCount('_test'));
		$this->dbu->delete('_test', ['id' => 123]);
		$this->assertEquals(3, $this->dbu->fetchCount('_test'));
	}

	public function testTransactionWorkflowWorks() {
		$this->assertEquals(3, $this->dbu->fetchCount('_test'));

		$this->dbu->begin();
		$this->dbu->delete('_test', ['id' => 1]);
		$this->assertEquals(2, $this->dbu->fetchCount('_test'));

		$this->dbu->rollback();
		$this->assertEquals(3, $this->dbu->fetchCount('_test'));

		$this->dbu->begin();
		$this->dbu->delete('_test', ['id' => 1]);
		$this->assertEquals(2, $this->dbu->fetchCount('_test'));
		$this->dbu->commit();

		$this->assertEquals(2, $this->dbu->fetchCount('_test'));
	}

	public function testDefaultStrictBehaviourThrowsWhenCommitOrRollbackWhileNotInTransaction() {
		$this->expectNotToPerformAssertions();
		try {
			$this->dbu->rollback();
			$this->fail('Should have not allowed rollback');
		} catch (\PDOException $e) {
		}

		try {
			$this->dbu->commit();
			$this->fail('Should have not allowed commit');
		} catch (\PDOException $e) {
		}
	}

	public function testRelaxedBehaviourIsSilentWhenCommitOrRollbackWhileNotInTransaction() {
		$this->expectNotToPerformAssertions();
		$this->dbu->rollback(false);
		$this->dbu->commit(false);
	}

	public function testQueryLogWorks() {
		// tento nebude zaratany
		$this->dbu->insert('_test', ['uid' => 1]);

		$this->dbu->activateQueryLog(true);
		$prepared = $this->dbu->prepare("update _test set foo = 'x' where id = :id");

		$this->dbu->insert('_test', ['uid' => 2]); // 1
		$this->dbu->insert('_test', ['uid' => 3]); // 2
		$this->dbu->delete('_test', ['uid' => 3]); // 3
		$this->dbu->fetchOne('uid', '_test'); // 4
		$this->dbu->execute("update _test set foo = 'pfuj'"); // 5
		for ($i = 0; $i < 3; $i++) {
			$this->dbu->execute($prepared, [':id' => $i]); // 8
		}
		$this->dbu->fetchRow('*', '_test'); // 9

		$log = $this->dbu->getQueryLog();
		$this->assertCount(9, $log);

		// deaktivujeme
		$this->dbu->activateQueryLog(false);
		$this->dbu->fetchRow('*', '_test');
		$this->assertNull($this->dbu->getQueryLog());
	}

	public function testInactiveQueryLogIsAlwaysNullInternally() {
		// tento nebude zaratany
		$this->dbu->activateQueryLog(true);
		$this->dbu->insert('_test', ['uid' => 1]);

		$this->assertNotEmpty($this->dbu->getQueryLog());

		// teraz ked ho vypneme
		$this->dbu->activateQueryLog(false);

		$this->dbu->insert('_test', ['uid' => 2]);

		// tak vyssie inserty nemaju vplyv a stale je null
		$this->assertNull($this->dbu->getQueryLog());
	}

	public function testValueIsNotEscapedInUpdateIfMagicOperatorIsUsed() {
		$before = $this->dbu->fetchAll('*', '_test', null, ['order_by' => 'id']);
		$this->dbu->update(
			'_test',
			[
				'foo=' => 'foo',
				// 'foo' => function(){return 'trim';},
				// 'foo' => 'trim',
			],
			null,
			0,
		);
		$after = $this->dbu->fetchAll('*', '_test', null, ['order_by' => 'id']);
		$this->assertEquals($before, $after);
		// prx($this->dbu->fetchAll('*', '_test'));
	}

	public function testExternalStaticLoggerWorks() {
		$x = [];
		//DbUtilPdo::$logger = function ($sql, $extra) use (&$x) {
		$this->dbu->logger = function ($sql, $extra) use (&$x) {
			$x[] = [$sql, $extra];
		};

		$this->dbu->insert('_test', ['uid' => 123, 'foo' => 456]);
		$this->dbu->update('_test', ['foo' => 789], 'id=1');

		//prx($x);
		$this->assertCount(2, $x);
		$this->assertEquals(1, preg_match('/123/', $x[0][0]));
		$this->assertEquals(1, preg_match('/456/', $x[0][0]));
		$this->assertEquals(1, preg_match('/789/', $x[1][0]));
	}

	public function testExtractingSignNotationFromColumntNameIsPublicAndWorks() {
		$colNameToExpectedSign = $backup = [
			'some' => '=',
			'some=' => '=',
			'some!' => '<>',
			'some>' => '>',
			'some>=' => '>=',
			'some<' => '<',
			'some<=' => '<=',
		];
		foreach ($colNameToExpectedSign as $col => $sign) {
			$result = DbUtilPdo::getSignFromColNotation($col);
			$this->assertEquals($sign, $result['sign']);
			$this->assertEquals('some', $result['column']);
		}

		// usitime sa, ze vyssim volanim sme nemodifikovali original
		$this->assertSame($colNameToExpectedSign, $backup);
	}

	public function testGroupByAddonWorks() {
		$db = $this->dbu;

		// prvym dvom nastavime rovnake foo
		$db->update('_test', ['foo' => 'foo'], 'id<3');

		// fetchneme zgroupnute
		$col = $this->dbu->fetchCol('foo', '_test', null, [
			'group_by' => 'foo',
			// 'debug' => 'die'
		]);

		sort($col);

		$this->assertCount(2, $col); // vysledkov musi byt 2 (nie 3)
		$this->assertEquals('foo', $col[0]);
		$this->assertEquals('wow', $col[1]);
	}

	public function testFetchingPairsWorks() {
		$db = $this->dbu;

		// vyrobime si pary rucne
		$rows = $db->fetchAll(
			'*',
			'_test',
			[
				'id>' => 1, // dame umyselne nieco aby sme videli ze to je efektivne
			],
			[
				'order_by' => 'id desc',
			],
		);
		$pairs = [];
		foreach ($rows as $row) {
			$pairs[$row['id']] = $row['foo'];
		}

		// a musia sa rovnat
		$this->assertSame(
			$pairs,
			$db->fetchPairs('id', 'foo', '_test', 'id>1', ['order_by' => 'id desc']),
		);
	}

	public function testGetColumnsWorks() {
		$db = $this->dbu;
		$cols = $db->getColumns('_test');
		$expected = ['id', 'uid', 'foo'];
		$this->assertEquals(array_keys($cols), $expected);
	}

	public function testQueryLogCounterWorks() {
		$db = $this->dbu;

		$db->resetQueryLogCounter();
		$this->assertEquals(0, $db->getQueryLogCounter());

		// urobime zopar roznych queries
		$db->begin();
		$db->fetchAll('*', '_test');
		$db->execute('update _test set foo = 123');
		$db->delete('_test', ['foo' => 123]);
		$db->insert('_test', ['uid' => 456]);
		$db->commit();
		$db->lastInsertId();

		//
		$this->assertEquals(7, $db->getQueryLogCounter());
	}

	public function testDeleteWithAddonLimitWorkForMysql() {
		$db = $this->dbu;

		// toto podporuje iba mysql
		if (!$db->isMysql()) {
			$this->markTestSkipped();
		}

		$rows = $db->fetchAll('*', '_test');
		$this->assertCount(3, $rows);

		$db->delete('_test', true, ['limit' => 2]);

		$rows = $db->fetchAll('*', '_test');
		$this->assertCount(1, $rows);
	}

	public function testGetTablesWorks() {
		$db = $this->dbu;

		$tables = $db->getTables();

		$this->assertTrue(in_array('_test', $tables));
	}
}
